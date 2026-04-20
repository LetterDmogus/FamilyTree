Konsep sistem chatot ai agent bersifat ReAct. Agent bisa memilih informasi apa yang penting, sistem ini memprioritaskan Harness yang simple tapi efektif. Model yang akan digunakan nanti adalah LLAMA 70B Versatile dari Groq.

## Core Idea

AI tidak pegang data — AI pegang **tools untuk query data**. Setiap kali butuh info, dia panggil tool, dapat hasil, lalu reasoning lagi.

```
User: "Siapa cucu tertua saya?"
  → AI: butuh data cucu dulu
  → CALL: filter({ relation: 'cucu' })
  → CALL: sort({ field: 'birth_date', order: 'asc' })
  → CALL: select({ limit: 1 })
  → AI: "Cucu tertua Anda adalah Jonathan, lahir 12 Maret 1995"
```

---


```javascript
// Satu tool yang handle semuanya
queryFamily({
  operations: [
    { op: 'filter', relation: 'cucu' },
    { op: 'sort', field: 'birth_date', order: 'asc' },
    { op: 'select', limit: 1, fields: ['name', 'birth_date'] }
  ]
})
```

| Tool | Fungsi |
|---|---|
| `queryFamily({ operations[] })` | Filter, sort, select, aggregate — semua via pipeline |
| `traverse({ id, type, depth })` | Navigasi tree: ancestors, descendants, path |
| `getSummary()` | Metadata awal: total, generasi, dll |

---

## Cara AI Menggunakannya

```
User: "Siapa cucu tertua?"

AI: Saya perlu cucu, diurutkan by lahir
→ CALL queryFamily({
    operations: [
      { op: 'filter', relation: 'cucu' },
      { op: 'sort', field: 'birth_date', order: 'asc' },
      { op: 'select', limit: 1 }
    ]
  })
→ RESULT: [{ name: 'Jonathan', birth_date: '1995-03-12' }]

AI: "Cucu tertua Anda adalah Jonathan, lahir 12 Maret 1995"
```

---

## Implementasi Query Engine

```javascript
// queryEngine.js
export function queryFamily(treeData, operations) {
  let result = flattenTree(treeData) // jadikan array dulu
  
  for (const op of operations) {
    if (op.op === 'filter') result = applyFilter(result, op)
    if (op.op === 'sort')   result = applySort(result, op)
    if (op.op === 'select') result = applySelect(result, op)
    if (op.op === 'aggregate') return applyAggregate(result, op) // early return
  }
  
  return result
}
```

Setiap baris tool excecution dijalankan, sistem akan mengirim status agent nya lagi ngapain dan tool yang dijalankan.

Pipeline dieksekusi **berurutan di client** — AI hanya kirim schema operasinya, tidak perlu tahu cara implementasinya.

---

## Kesimpulan

- AI **tidak perlu** call function in function
- Cukup **1 tool call** dengan array operations yang di-chain
- Logic kombinasinya ada di **Query Engine (client-side)**, bukan di AI
- AI tugasnya hanya: *"operasi apa yang perlu dijalankan?"*

## Arsitektur yang Disarankan

```
FamilyTree Data (full, di client)
        ↓
  Query Engine (JS)     ← eksekusi tools, data tidak kemana-mana
        ↓
  Tool Results (ringkas) ← hanya hasil query yang dikirim ke AI
        ↓
    Claude API          ← reasoning + jawaban natural
        ↓
      User
```

**Keuntungan:** data tidak pernah keluar dari client, context window aman, privacy terjaga.

Groq pilihan yang tepat untuk free tier! Llama 70B juga cukup capable untuk ReAct pattern.

## Yang Perlu Diperhatikan dengan Groq

Groq punya **tool calling native** untuk Llama 70B, jadi tidak perlu implementasi ReAct manual. Tapi kalau mau pakai ReAct murni via system prompt juga bisa.

---

## Opsi 1: Pakai Tool Calling Native Groq (Lebih Mudah)

```javascript
const response = await fetch("https://api.groq.com/openai/v1/chat/completions", {
  method: "POST",
  headers: {
    "Authorization": `Bearer ${GROQ_API_KEY}`,
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    model: "llama-3.3-70b-versatile",
    messages: [...],
    tools: [
      {
        type: "function",
        function: {
          name: "queryFamily",
          description: "Query anggota keluarga dengan filter, sort, select",
          parameters: {
            type: "object",
            properties: {
              operations: {
                type: "array",
                items: {
                  type: "object",
                  properties: {
                    op: { type: "string", enum: ["filter", "sort", "select", "aggregate"] },
                    // ... field lainnya
                  }
                }
              }
            }
          }
        }
      }
    ],
    tool_choice: "auto"
  })
})
```

Format ini **OpenAI-compatible**, jadi Groq langsung support.

---

## System Prompt

```
You are a helpful family tree assistant. 
The user is viewing their family tree application.

You have access to tools to query the family data.
ALWAYS use tools first before answering questions about specific people or statistics.
Do NOT assume or hallucinate family data — only use what the tools return.

When answering:
- Be concise and friendly
- Use the user's local language (follow whatever language the user uses)
- If tool returns empty, say the data is not found
- Never expose raw JSON to the user

Current context:
```

Context `{}` diisi dinamis dari `getSummary()` sebelum chat dimulai — jadi AI punya orientasi dasar tanpa harus query dulu.

---

## Flow Lengkapnya

```
1. App load → getSummary() → inject ke system prompt
2. User kirim pesan
3. Groq decide: perlu tool atau tidak?
4. Kalau perlu → return tool_call
5. App eksekusi queryFamily() / traverse() di client
6. Hasil dikirim balik ke Groq sebagai tool_result
7. Groq jawab dalam bahasa natural
```

---
User command:

/compact — Ringkas Context
User: /compact

AI: [baca semua history]
    → buat ringkasan: "Sejauh ini user bertanya tentang 
      cucu tertua, total anggota laki-laki, dan silsilah 
      dari pihak ibu..."
    
History lama dibuang, diganti 1 message ringkasan

/clear - membersihkan chat lama
