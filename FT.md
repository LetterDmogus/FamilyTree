## Konsep Sistem Family Tree

### Desain Database

**Tabel `users`** — data user biasa (id, name, email, dst)

**Tabel `family_nodes`** — ini tabel pivot relasi antar user:

```sql
family_nodes
- id
- user_id        -- si "penghubung" (yang add)
- related_user_id -- user yang di-add
- relationship_type  -- ENUM: 'child', 'spouse', 'connection'
 created_at
```

---

### Logika Backend (Laravel)

**Model `FamilyNode`:**
```php
class FamilyNode extends Model
{
    protected $fillable = ['user_id', 'related_user_id', 'relationship_type'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function relatedUser() {
        return $this->belongsTo(User::class, 'related_user_id');
    }
}
```

**Di `User` model, tambahkan relasi:**
```php
public function children() {
    return $this->belongsToMany(User::class, 'family_nodes', 'user_id', 'related_user_id')
        ->wherePivot('relationship_type', 'child');
}

public function spouse() {
    return $this->belongsToMany(User::class, 'family_nodes', 'user_id', 'related_user_id')
        ->wherePivot('relationship_type', 'spouse');
}

public function connections() {
    return $this->belongsToMany(User::class, 'family_nodes', 'user_id', 'related_user_id')
        ->wherePivot('relationship_type', 'connection');
}
```

---

### Recursive Tree Builder (max 3 tier)

Ini bagian intinya — fungsi rekursif yang berhenti di depth 3:

```php
// FamilyTreeService.php
class FamilyTreeService
{
    public function buildTree(User $rootUser, int $maxDepth = 3): array
    {
        return $this->buildNode($rootUser, 1, $maxDepth);
    }

    private function buildNode(User $user, int $currentDepth, int $maxDepth): array
    {
        $node = [
            'id'    => $user->id,
            'name'  => $user->name,
            'depth' => $currentDepth,
            'spouse'      => [],
            'children'    => [],
            'connections' => [],
        ];

        if ($currentDepth >= $maxDepth) {
            // Sudah di tier 3, stop rekursi
            return $node;
        }

        // Load spouse (biasanya tidak turun tier)
        foreach ($user->spouse as $spouse) {
            $node['spouse'][] = [
                'id'   => $spouse->id,
                'name' => $spouse->name,
            ];
        }

        // Load children — ini yang turun tier
        foreach ($user->children as $child) {
            $node['children'][] = $this->buildNode($child, $currentDepth + 1, $maxDepth);
        }

        // Load connections (flat, tidak turun tier)
        foreach ($user->connections as $conn) {
            $node['connections'][] = [
                'id'   => $conn->id,
                'name' => $conn->name,
            ];
        }

        return $node;
    }
}
```

**Di controller:**
```php
public function show(User $user)
{
    $tree = (new FamilyTreeService())->buildTree($user);

    return Inertia::render('FamilyTree/Show', [
        'tree' => $tree,
    ]);
}
```

---

### Struktur Output JSON ke Vue

```json
{
  "id": 1, "name": "Kakek", "depth": 1,
  "spouse": [{ "id": 2, "name": "Nenek" }],
  "children": [
    {
      "id": 3, "name": "Ayah", "depth": 2,
      "spouse": [{ "id": 4, "name": "Ibu" }],
      "children": [
        { "id": 5, "name": "Anak 1", "depth": 3, "children": [] },
        { "id": 6, "name": "Anak 2", "depth": 3, "children": [] }
      ]
    }
  ]
}
```

---

### Catatan Penting

| Hal | Saran |
|---|---|
| **Spouse bidirectional** | Saat insert spouse, insert 2 baris (A→B dan B→A) supaya relasi simetris |
| **Cegah circular** | Tambah validasi: user tidak bisa add dirinya sendiri atau yang sudah jadi parent-nya |
| **Print 3 tier** | Gunakan prop `maxDepth` dari controller, pass ke Vue, lalu CSS `@media print` hide tier > 3 |

Dengan struktur ini, Vue tinggal terima satu object tree dan render secara rekursif pakai component `<TreeNode>` yang memanggil dirinya sendiri.

Sistem **tidak menyimpan** label "sepupu" atau "paman" di database. Label itu **dihitung saat runtime** dengan cara traverse tree.

### Logikanya:

```
Untuk tau relasi A → C:
1. Cari path dari A ke C lewat tree
2. Analisa path tersebut → tentukan labelnya
```

**Contoh path:**
```
A → (child of) → B → (child of) → C
Artinya: A adalah cucu C, atau C adalah kakek A
```

### Aturan Mapping Path:

```php
class RelationshipCalculator
{
    public function calculate(User $from, User $to): string
    {
        $path = $this->findPath($from, $to);
        // path berisi arah: 'up' (ke parent) atau 'down' (ke child)
        // contoh: ['up', 'up', 'down', 'down'] = sepupu

        return $this->interpretPath($path);
    }

    private function interpretPath(array $path): string
    {
        $ups   = count(array_filter($path, fn($p) => $p === 'up'));
        $downs = count(array_filter($path, fn($p) => $p === 'down'));

        // Normalisasi: semua 'up' dulu baru 'down'
        return match(true) {
            $ups === 1 && $downs === 0 => 'Orang Tua',
            $ups === 0 && $downs === 1 => 'Anak',
            $ups === 2 && $downs === 0 => 'Kakek/Nenek',
            $ups === 0 && $downs === 2 => 'Cucu',
            $ups === 1 && $downs === 1 => 'Saudara Kandung',
            $ups === 2 && $downs === 1 => 'Paman/Bibi',
            $ups === 1 && $downs === 2 => 'Keponakan',
            $ups === 2 && $downs === 2 => 'Sepupu',
            $ups === 3 && $downs === 1 => 'Kakek/Nenek Buyut (sisi atas)',
            default => 'Kerabat Jauh'
        };
    }
}
```

### Cara findPath (BFS):

```php
private function findPath(User $from, User $to): ?array
{
    // BFS dengan tracking arah gerak (naik ke parent / turun ke child)
    $queue   = [[$from->id, []]];
    $visited = [$from->id];

    while (!empty($queue)) {
        [$currentId, $path] = array_shift($queue);
        $current = User::find($currentId);

        // Cek parent (gerak 'up')
        $parents = FamilyNode::where('related_user_id', $currentId)
            ->where('relationship_type', 'child')
            ->pluck('user_id');

        foreach ($parents as $parentId) {
            if ($parentId === $to->id) return [...$path, 'up'];
            if (!in_array($parentId, $visited)) {
                $visited[] = $parentId;
                $queue[]   = [$parentId, [...$path, 'up']];
            }
        }

        // Cek children (gerak 'down')
        foreach ($current->children as $child) {
            if ($child->id === $to->id) return [...$path, 'down'];
            if (!in_array($child->id, $visited)) {
                $visited[] = $child->id;
                $queue[]   = [$child->id, [...$path, 'down']];
            }
        }
    }

    return null; // tidak ada koneksi

COntoh tabel relationnya:
Schema::create('relations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('person_id');
    $table->foreignId('related_person_id');
    $table->enum('type', ['parent', 'child', 'spouse']); 
    $table->boolean('is_blood')->default(true); // <--- Trik buatanmu
    $table->timestamps();
});

Kemudian untuk sistem user detailnya, menyimpan data wajib dan json (tidak wajib)

nama_lengkap (Wajib untuk tampilan)
jenis_kelamin (Wajib untuk hitung panggilan Ayah/Ibu)
tanggal_lahir (Wajib untuk hitung umur Kakak/Adik)
is_kepala_keluarga (Wajib untuk logika pusat pohon)

konsep Master Data Management JSON (data tidak wajib).

Cara Kerjanya: Kamu buat satu tabel referensi bernama master_social_medias yang berisi id, nama (Facebook, X, Instagram), dan icon_url (atau class SVG/FontAwesome).

Di Sisi Frontend (Vue.js): Saat user mau tambah sosmed, komponen search-select (seperti vue-select atau headlessui/combobox) akan memanggil data dari tabel master ini. Jadi user tidak bisa mengetik "fb" sembarangan.

Di Sisi Database (user_profiles): Data JSON yang tersimpan akan sangat rapi. Bentuknya bisa berupa array of objects yang menyimpan ID dari master dan username-nya:

JSON
{
  "sosial_media": [
    {"platform_id": 1, "platform_name": "Facebook", "username": "budi_123"},
    {"platform_id": 2, "platform_name": "Instagram", "username": "budi.arts"}
  ]
}}

Bagaimana dengan Data "Cerita" atau Prestasi?
Metode Master Table dan Search-Select tidak cocok untuk data seperti cerita hidup, karena sifatnya unstructured (teks bebas yang panjang dan unik untuk tiap orang).

Untuk data ini, biarkan saja tetap berupa Text Area biasa di frontend, lalu simpan ke dalam JSON sebagai string panjang atau dipisah berdasarkan tahun.

JSON
{
  "cerita": "Saya lahir di Batam dan mulai belajar web dev sejak SMP...",
  "timeline": [
    {"tahun": "2024", "event": "Masuk SMK Jurusan RPL"}
  ]
}

Logika Tampilan:
User klik node "Ayah"
  → Panel kanan muncul:
      📋 Nama: Budi Santoso
      🔗 Relasi: Anak dari Kakek

      [ + Tambah Anak ]
      [ + Tambah Pasangan ]
      [ + Tambah Koneksi ]
      [ ✏️ Edit ]
      [ 🗑️ Hapus ]

Ketika klik salah satu tombol tambah → modal kecil muncul:
Modal "Tambah Anak untuk Budi"
┌─────────────────────────────┐
│ Cari user yang sudah ada... │  ← autocomplete search
│ ── atau ──                  │
│ [ Buat user baru ]          │  ← form nama, dsb
└─────────────────────────────┘
Kenapa Autocomplete Search Penting?
Dengan 20-50 orang, kemungkinan user yang sama muncul di konteks berbeda itu tinggi (misalnya orang yang menikah masuk ke 2 keluarga). Jadi sistem harus bisa link ke user existing, bukan selalu buat baru.

Struktur Vue Component-nya:
vue<!-- FamilyTree.vue -->
<template>
  <div class="flex h-screen">

    <!-- Area Tree (kiri) -->
    <div class="flex-1 overflow-auto">
      <TreeNode
        :node="tree"
        @node-click="handleNodeClick"
      />
    </div>

    <!-- Panel Detail (kanan) -->
    <NodePanel
      v-if="selectedNode"
      :node="selectedNode"
      @add-relation="openAddModal"
      @close="selectedNode = null"
    />

    <!-- Modal Tambah -->
    <AddRelationModal
      v-if="addModal.open"
      :parent="selectedNode"
      :type="addModal.type"
      @confirm="handleAddRelation"
      @close="addModal.open = false"
    />

  </div>
</template>

<script setup>
import { ref } from 'vue'

const selectedNode = ref(null)
const addModal = ref({ open: false, type: null })

function handleNodeClick(node) {
  selectedNode.value = node
}

function openAddModal(type) {
  // type: 'child' | 'spouse' | 'connection'
  addModal.value = { open: true, type }
}

async function handleAddRelation({ userId, isNew, formData }) {
  // Kalau isNew → POST /users dulu, dapat id baru
  // Lalu POST /family-nodes
  await axios.post('/family-nodes', {
    user_id: selectedNode.value.id,
    related_user_id: userId,
    relationship_type: addModal.value.type,
  })

  // Refresh tree
  router.reload()
}
</script>
Summary Flow Lengkap:
Klik node
  └→ Panel terbuka
        └→ Klik "Tambah Anak"
              └→ Modal muncul
                    ├→ Search user existing → pilih → submit
                    └→ Buat baru → isi form → submit
                              └→ POST ke backend
                                    └→ Tree re-render otomatis
Pendekatan ini juga memudahkan fitur print — panel dan modal bisa di-hide dengan @media print, jadi yang tercetak hanya tree-nya saja.


