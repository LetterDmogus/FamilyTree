<?php

namespace App\Http\Controllers;

use App\Models\FamilyLetter;
use App\Models\MasterAdditionalField;
use App\Models\MasterSocialMedia;
use App\Models\Relation;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAttachment;
use App\Models\UserProfile;
use App\Services\FamilyTreeService;
use App\Services\RelationshipCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class FamilyTreeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected FamilyTreeService $treeService,
        protected RelationshipCalculator $calculator
    ) {}

    public function downloadMockupKk()
    {
        $path = storage_path('app/public/mockup-kk.xlsx');

        $writer = SimpleExcelWriter::create($path)
            ->addRow([
                'NAMA LENGKAP' => 'BUDI SANTOSO',
                'NIK' => '3301234567890001',
                'JENIS KELAMIN' => 'Laki-laki',
                'TEMPAT LAHIR' => 'Jakarta',
                'TANGGAL LAHIR' => '1980-05-15',
                'AGAMA' => 'Islam',
                'PENDIDIKAN' => 'S1',
                'JENIS PEKERJAAN' => 'Karyawan Swasta',
                'STATUS PERKAWINAN' => 'Kawin',
                'STATUS HUBUNGAN DALAM KELUARGA' => 'KEPALA KELUARGA',
                'KEWARGANEGARAAN' => 'WNI',
            ])
            ->addRow([
                'NAMA LENGKAP' => 'SITI AMINAH',
                'NIK' => '3301234567890002',
                'JENIS KELAMIN' => 'Perempuan',
                'TEMPAT LHIR' => 'Bandung',
                'TANGGAL LAHIR' => '1982-08-20',
                'AGAMA' => 'Islam',
                'PENDIDIKAN' => 'S1',
                'JENIS PEKERJAAN' => 'Ibu Rumah Tangga',
                'STATUS PERKAWINAN' => 'Kawin',
                'STATUS HUBUNGAN DALAM KELUARGA' => 'ISTRI',
                'KEWARGANEGARAAN' => 'WNI',
            ])
            ->addRow([
                'NAMA LENGKAP' => 'ANDI SANTOSO',
                'NIK' => '3301234567890003',
                'JENIS KELAMIN' => 'Laki-laki',
                'TEMPAT LAHIR' => 'Jakarta',
                'TANGGAL LAHIR' => '2010-12-10',
                'AGAMA' => 'Islam',
                'PENDIDIKAN' => 'SD',
                'JENIS PEKERJAAN' => 'Pelajar',
                'STATUS PERKAWINAN' => 'Belum Kawin',
                'STATUS HUBUNGAN DALAM KELUARGA' => 'ANAK',
                'KEWARGANEGARAAN' => 'WNI',
            ]);

        return response()->download($path, 'Template_KK_Wisemystical.xlsx')->deleteFileAfterSend(true);
    }

    public function extractKkExcel(User $user, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        $rows = SimpleExcelReader::create($request->file('file')->getRealPath())->getRows();

        $extracted = $rows->map(function ($row) {
            // Normalize field names (handle slight variations or typos)
            $row = array_change_key_case($row, CASE_UPPER);

            $fullName = $row['NAMA LENGKAP'] ?? $row['NAMA'] ?? null;
            if (! $fullName) {
                return null;
            }

            $genderStr = $row['JENIS KELAMIN'] ?? '';
            $gender = (str_contains(strtolower($genderStr), 'perempuan') || strtolower($genderStr) === 'p' || strtolower($genderStr) === 'f') ? 'F' : 'M';

            $relationStr = strtolower($row['STATUS HUBUNGAN DALAM KELUARGA'] ?? $row['HUBUNGAN'] ?? '');
            $type = 'child'; // Default
            if (str_contains($relationStr, 'istri') || str_contains($relationStr, 'suami')) {
                $type = 'spouse';
            } elseif (str_contains($relationStr, 'kepala')) {
                $type = 'self';
            }

            return [
                'full_name' => $fullName,
                'email' => strtolower(str_replace(' ', '.', $fullName)).'@example.com', // Placeholder
                'gender' => $gender,
                'birth_date' => $row['TANGGAL LAHIR'] ?? null,
                'birth_place' => [
                    'city' => $row['TEMPAT LAHIR'] ?? $row['TEMPAT LHIR'] ?? '',
                    'country' => 'Indonesia',
                ],
                'relation_type' => $type,
                'nik' => $row['NIK'] ?? null,
                'occupation' => $row['JENIS PEKERJAAN'] ?? null,
            ];
        })->filter()->values();

        return response()->json($extracted);
    }

    public function show(Request $request, ?User $user = null)
    {
        $viewer = auth()->user();
        $targetUser = $user ?? $viewer;
        $expandedIds = $request->input('expanded', []);

        $root = $this->findVisualRoot($targetUser);

        $settings = Setting::all()->mapWithKeys(fn ($s) => [$s->key => $s->cast_value]);
        $maxDepth = (int) ($settings['priority_limit'] ?? 5);

        $tree = $this->treeService->buildTree($root, $viewer, $maxDepth, $expandedIds);

        return Inertia::render('FamilyTree/Show', [
            'tree' => $tree,
            'rootUser' => $root,
            'master' => [
                'socialMedias' => MasterSocialMedia::all(),
                'additionalFields' => MasterAdditionalField::all(),
                'settings' => $settings,
            ],
            'can' => [
                'manage_all' => $viewer->can('manage_tree_all'),
                'manage_self' => $viewer->can('manage_tree_self'),
                'delete_all' => $viewer->can('delete_node_all'),
            ],
        ]);
    }

    private function findVisualRoot(User $user): User
    {
        $parentId = Relation::where('related_user_id', $user->id)
            ->where('type', 'child')
            ->value('user_id');

        if (! $parentId) {
            $spouseId = Relation::where('user_id', $user->id)
                ->where('type', 'spouse')
                ->value('related_user_id');

            if ($spouseId) {
                return $this->findVisualRoot(User::find($spouseId));
            }

            return $user;
        }

        $parent = User::find($parentId);

        $grandParentId = Relation::where('related_user_id', $parent->id)
            ->where('type', 'child')
            ->value('user_id');

        if (! $grandParentId) {
            return $parent;
        }

        return User::find($grandParentId) ?? $parent;
    }

    public function locations()
    {
        $profiles = UserProfile::whereNotNull('birth_place')
            ->orWhereNotNull('death_place')
            ->orWhereNotNull('current_address')
            ->get(['user_id', 'full_name', 'birth_place', 'death_place', 'current_address']);

        $locations = [];
        foreach ($profiles as $profile) {
            // Helper function to check if a location object has meaningful data
            $hasData = fn ($place) => ! empty($place['country']) || ! empty($place['city']) || ! empty($place['address']);

            if ($profile->birth_place && isset($profile->birth_place['lat']) && $hasData($profile->birth_place)) {
                $locations[] = [
                    'user_id' => $profile->user_id,
                    'full_name' => $profile->full_name,
                    'type' => 'birth',
                    'place' => $profile->birth_place,
                ];
            }
            if ($profile->death_place && isset($profile->death_place['lat']) && $hasData($profile->death_place)) {
                $locations[] = [
                    'user_id' => $profile->user_id,
                    'full_name' => $profile->full_name,
                    'type' => 'death',
                    'place' => $profile->death_place,
                ];
            }
            if ($profile->current_address && isset($profile->current_address['lat']) && $hasData($profile->current_address)) {
                $locations[] = [
                    'user_id' => $profile->user_id,
                    'full_name' => $profile->full_name,
                    'type' => 'current',
                    'place' => $profile->current_address,
                ];
            }
        }

        return response()->json($locations);
    }

    public function details(User $user, Request $request)
    {
        $fromId = $request->input('from_id', auth()->id());
        $fromUser = User::find($fromId) ?? auth()->user();

        $user->load(['profile', 'roles', 'parents.profile', 'spouse.profile', 'children.profile']);
        $relationLabel = $this->calculator->calculate($fromUser, $user);

        // Enhance social media data with prefixes
        $profile = $user->profile;
        if ($profile && ! empty($profile->social_media)) {
            $platforms = MasterSocialMedia::all();
            $enhancedSocial = array_map(function ($sm) use ($platforms) {
                $platform = $platforms->firstWhere('name', $sm['platform_name']);

                return array_merge($sm, [
                    'prefix' => $platform ? $platform->prefix : '',
                ]);
            }, $profile->social_media);
            $profile->social_media = $enhancedSocial;
        }

        // Point 7, 9 & 16: Fetch Attachments
        $viewer = auth()->user();
        $isSelf = $viewer->id === $user->id;
        $isChild = $viewer->isChildOf($user);
        $isParent = $viewer->isParentOf($user) || $viewer->isStepParentOf($user);
        $isSpouse = $user->spouse()->where('related_user_id', $viewer->id)->exists();

        $attachments = [
            'history' => $user->attachments()->where('category', 'history')->latest()->get(),
            'identity' => ($isSelf || $isChild || $isSpouse)
                ? $user->attachments()->where('category', 'identity')->get()
                : [],
        ];

        // Fetch Family Letters (Conversation History between Viewer and Target)
        $letters = [];
        if ($isSelf) {
            // If viewing self, show all received letters
            $letters = $user->receivedLetters()->with('sender.profile')->latest()->get();
        } else {
            // If viewing someone else, show conversation history between viewer and that person
            $letters = FamilyLetter::where(function ($q) use ($viewer, $user) {
                $q->where('sender_id', $viewer->id)->where('recipient_id', $user->id);
            })->orWhere(function ($q) use ($viewer, $user) {
                $q->where('sender_id', $user->id)->where('recipient_id', $viewer->id);
            })->with(['sender.profile', 'recipient.profile'])->latest()->get();
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'full_name' => $user->profile->full_name ?? $user->name,
            'email' => $user->email,
            'relation_label' => $relationLabel,
            'profile' => $profile,
            'attachments' => $attachments,
            'letters' => $letters,
            'relations' => [
                'parents' => $user->parents->map(fn ($u) => [
                    'id' => $u->id,
                    'full_name' => $u->profile->full_name ?? $u->name,
                    'photo_url' => $u->profile->profile_photo_path ? '/storage/'.$u->profile->profile_photo_path : null,
                ]),
                'spouses' => $user->spouse->map(fn ($u) => [
                    'id' => $u->id,
                    'full_name' => $u->profile->full_name ?? $u->name,
                    'photo_url' => $u->profile->profile_photo_path ? '/storage/'.$u->profile->profile_photo_path : null,
                ]),
                'children' => $user->children->map(fn ($u) => [
                    'id' => $u->id,
                    'full_name' => $u->profile->full_name ?? $u->name,
                    'photo_url' => $u->profile->profile_photo_path ? '/storage/'.$u->profile->profile_photo_path : null,
                ]),
            ],
            'is_admin' => $user->can('manage_tree_all'),
            'can' => [
                'toggle_admin' => auth()->user()->can('manage_roles'),
                'delete' => (auth()->user()->isParentOf($user) || auth()->user()->isStepParentOf($user)) && auth()->id() !== $user->id,
                'edit' => auth()->user()->can('manage_tree_all') || (auth()->user()->can('manage_tree_self') && auth()->id() === $user->id),
                'set_head' => auth()->user()->can('manage_tree_all'),
                'upload_identity' => $isSelf,
                'write_note' => $isParent, // Only parents can write letters to this child
            ],
        ]);
    }

    public function storeLetter(User $user, Request $request)
    {
        $viewer = auth()->user();
        if (! $viewer->isParentOf($user) && ! $viewer->isStepParentOf($user)) {
            abort(403, 'Hanya orang tua yang dapat mengirim surat keluarga kepada anak.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'style' => 'nullable|string|in:classic,formal,paper',
        ]);

        FamilyLetter::create([
            'sender_id' => $viewer->id,
            'recipient_id' => $user->id,
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'style' => $validated['style'] ?? 'classic',
        ]);

        return back()->with('success', 'Surat keluarga berhasil dikirim.');
    }

    public function markLetterAsRead(FamilyLetter $letter)
    {
        if (auth()->id() !== $letter->recipient_id) {
            abort(403);
        }

        if (! $letter->read_at) {
            $letter->update(['read_at' => now()]);
            $this->clearTreeCache();
        }

        return response()->json(['success' => true]);
    }

    public function uploadIdentity(User $user, Request $request)
    {
        if (auth()->id() !== $user->id) {
            abort(403, 'Hanya Anda yang dapat mengunggah dokumen identitas Anda sendiri.');
        }

        $validated = $request->validate([
            'type' => 'required|in:kk,ktp',
            'file' => 'required|image|max:4096',
        ]);

        $path = $request->file('file')->store('identities', 'public');

        UserAttachment::updateOrCreate(
            ['user_id' => $user->id, 'type' => $validated['type'], 'category' => 'identity'],
            ['file_path' => $path, 'is_private' => true]
        );

        return back()->with('success', strtoupper($validated['type']).' berhasil diunggah.');
    }

    public function toggleFamilyHead(User $user)
    {
        $this->authorize('manage_tree_all');

        $user->profile->update([
            'is_family_head' => ! $user->profile->is_family_head,
        ]);

        $this->clearTreeCache();

        $status = $user->profile->is_family_head ? 'ditetapkan sebagai' : 'dicabut statusnya sebagai';

        return back()->with('success', "{$user->profile->full_name} {$status} Kepala Keluarga.");
    }

    public function storeRelation(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:child,spouse,parent',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date',
            'birth_place' => 'required|array',
            'profile_photo' => 'nullable|image|max:2048',
            'is_alive' => 'boolean',
            'death_date' => 'nullable|date',
            'death_place' => 'nullable|array',
            'current_address' => 'nullable|array',
            'additional_info' => 'nullable|array',
            'social_media' => 'nullable|array',
        ]);

        $targetUserId = $validated['user_id'];

        // Authorization check
        $canManage = auth()->user()->can('manage_tree_all') ||
                    (auth()->user()->can('manage_tree_self') && auth()->id() == $targetUserId);

        if (! $canManage) {
            abort(403, 'Anda tidak memiliki izin untuk menambah anggota ini.');
        }

        // Global Settings Validation
        $settings = Setting::all()->pluck('value', 'key');
        $allowSameSex = filter_var($settings['allow_same_sex'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $maxSpouses = (int) ($settings['max_spouses'] ?? 0);

        if ($validated['type'] === 'spouse') {
            $targetUser = User::with('profile')->find($targetUserId);

            // 1. Same Sex Check
            if (! $allowSameSex && $targetUser->profile->gender === $validated['gender']) {
                $siteName = Setting::getValue('site_name', 'Wise Mystical Tree');

                return back()->withErrors(['error' => "Aturan {$siteName} melarang penambahan pasangan dengan gender yang sama."]);
            }

            // 2. Max Spouse Check
            if ($maxSpouses > 0) {
                $currentSpouseCount = Relation::where('user_id', $targetUserId)->where('type', 'spouse')->count();
                if ($currentSpouseCount >= $maxSpouses) {
                    return back()->withErrors(['error' => "Batas maksimal pasangan ({$maxSpouses}) telah tercapai."]);
                }
            }

            // 3. Minimum Marriage Age Check (Poin 4)
            $minAge = (int) ($settings['min_marriage_age'] ?? 17);
            $birthDate = new Carbon($validated['birth_date']);
            if ($birthDate->age < $minAge) {
                return back()->withErrors(['error' => "Minimal umur untuk menikah adalah {$minAge} tahun (Anggota yang akan ditambah masih berumur {$birthDate->age} tahun)."]);
            }

            $targetBirthDate = new Carbon($targetUser->profile->birth_date);
            if ($targetBirthDate->age < $minAge) {
                return back()->withErrors(['error' => "{$targetUser->name} masih berumur {$targetBirthDate->age} tahun, belum mencapai batas minimal {$minAge} tahun untuk memiliki pasangan."]);
            }
        }

        $user = User::create([
            'name' => explode(' ', $validated['full_name'])[0],
            'email' => $validated['email'],
            'password' => bcrypt(str()->random(16)),
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profiles', 'public');
        }

        UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'birth_place' => $validated['birth_place'],
            'is_alive' => $validated['is_alive'] ?? true,
            'death_date' => $validated['death_date'] ?? null,
            'death_place' => $validated['death_place'] ?? null,
            'current_address' => $validated['current_address'] ?? null,
            'profile_photo_path' => $photoPath,
            'additional_info' => $validated['additional_info'] ?? [],
            'social_media' => $validated['social_media'] ?? [],
        ]);

        $user->assignRole('member');

        if ($validated['type'] === 'child') {
            Relation::create([
                'user_id' => $targetUserId,
                'related_user_id' => $user->id,
                'type' => 'child',
                'is_blood' => true,
            ]);

            // Link to selected spouse if provided in the request
            if ($request->filled('spouse_id')) {
                Relation::create([
                    'user_id' => $request->spouse_id,
                    'related_user_id' => $user->id,
                    'type' => 'child',
                    'is_blood' => true,
                ]);
            }
        } elseif ($validated['type'] === 'spouse') {
            Relation::create(['user_id' => $targetUserId, 'related_user_id' => $user->id, 'type' => 'spouse', 'is_blood' => false]);
            Relation::create(['user_id' => $user->id, 'related_user_id' => $targetUserId, 'type' => 'spouse', 'is_blood' => false]);
        } elseif ($validated['type'] === 'parent') {
            Relation::create([
                'user_id' => $user->id,
                'related_user_id' => $targetUserId,
                'type' => 'child',
                'is_blood' => true,
            ]);
        }

        $this->clearTreeCache();

        return back()->with('success', 'Anggota keluarga berhasil ditambahkan.');
    }

    public function updateProfile(User $user, Request $request)
    {
        // Authorization check
        $canEdit = auth()->user()->can('manage_tree_all') ||
                  (auth()->user()->can('manage_tree_self') && auth()->id() === $user->id);

        if (! $canEdit) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit profil ini.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date',
            'birth_place' => 'required|array',
            'is_alive' => 'boolean',
            'death_date' => 'nullable|date',
            'death_place' => 'nullable|array',
            'current_address' => 'nullable|array',
            'profile_photo' => 'nullable|image|max:2048',
            'additional_info' => 'nullable|array',
            'social_media' => 'nullable|array',
        ]);

        $user->update([
            'name' => explode(' ', $validated['full_name'])[0],
            'email' => $validated['email'],
        ]);

        // Loophole: Check if gender change violates same-sex rules for existing spouses
        if ($user->profile->gender !== $validated['gender']) {
            $allowSameSex = Setting::getValue('allow_same_sex', false);
            if (! $allowSameSex) {
                $hasOppositeSpouse = Relation::where('user_id', $user->id)
                    ->where('type', 'spouse')
                    ->whereHas('relatedUser.profile', function ($q) use ($validated) {
                        $q->where('gender', $validated['gender']);
                    })->exists();

                if ($hasOppositeSpouse) {
                    return back()->withErrors(['error' => 'Perubahan gender ditolak karena akan melanggar aturan pernikahan sesama gender pada pasangan yang ada.']);
                }
            }
        }

        $data = [
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'birth_place' => $validated['birth_place'],
            'is_alive' => $validated['is_alive'] ?? true,
            'death_date' => $validated['death_date'] ?? null,
            'death_place' => $validated['death_place'] ?? null,
            'current_address' => $validated['current_address'] ?? null,
            'additional_info' => $validated['additional_info'] ?? [],
            'social_media' => $validated['social_media'] ?? [],
        ];

        if ($request->hasFile('profile_photo')) {
            // Save old photo to history before replacing
            if ($user->profile->profile_photo_path) {
                UserAttachment::create([
                    'user_id' => $user->id,
                    'category' => 'history',
                    'type' => 'profile_photo',
                    'file_path' => $user->profile->profile_photo_path,
                    'metadata' => [
                        'replaced_at' => now()->toDateTimeString(),
                        'reason' => 'Profile update',
                    ],
                ]);
            }
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profiles', 'public');
            $data['profile_photo_updated_at'] = now();
        }

        $user->profile->update($data);

        $this->clearTreeCache();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function toggleAdmin(User $user)
    {
        // 1. Only superadmin or admin with manage_roles can promote others
        $this->authorize('manage_roles');

        // 2. Loophole Protection: Cannot revoke own admin status (self-lockout)
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'Anda tidak dapat mencabut hak akses admin Anda sendiri.']);
        }

        // 3. Superadmin Protection: Role cannot be toggled
        if ($user->hasRole('superadmin')) {
            return back()->withErrors(['error' => 'Hak akses Super Admin bersifat permanen.']);
        }

        if ($user->hasRole('admin')) {
            $user->removeRole('admin');
            $message = 'Hak akses admin dicabut.';
        } else {
            $user->assignRole('admin');
            $message = 'Anggota berhasil dipromosikan menjadi admin.';
        }

        $this->clearTreeCache();

        return back()->with('success', $message);
    }

    public function destroyMember(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus diri sendiri.']);
        }

        $isParent = auth()->user()->isParentOf($user);
        $isStepParent = auth()->user()->isStepParentOf($user);

        if (! $isParent && ! $isStepParent) {
            return back()->withErrors(['error' => 'Hanya orang tua (kandung atau tiri) yang dapat menghapus data anggota ini.']);
        }

        try {
            $user->delete();

            return back()->with('success', 'Anggota keluarga berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function clearTreeCache()
    {
        Cache::flush();
    }
}
