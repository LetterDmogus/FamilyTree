Roadmap test
Test 1:
1. Setiap element di diagram itu buat 1 user, jadi kalau ada suami istri itu harus dipisah dan anak mereka konek lewat garis diantara mereka.

2. User bisa lihat ke atas sampai kakek dan neneknya, dan kebawah sampai cucu nya.

3. Setiap user hanya bisa add anaknya atau pasangannya.

4. Sistem level dinamis, jadi tidak menampilkan levelnya.

5. user yang sedang login akan menjadi "saya", dan user lain akan memiliki julukannya masin masing, ikutin aturan di FT.md mengenai julukannya

Revision 1:
terkait migration nya, user profiles menyimpan 2 tipe json, satu untuk data sosial media, dan satu lagi untuk profile data opsional, misalnya tempat bekerja, dan pekerjaan nya. kemudian  tabelnya juga menyimpan foto profile dari setiap user nya. ketika di visualisasi kan di diagram, bentuknya itu ada profile, pangilan, dan nama lengkap mereka (efek dim atau lebih gelap dengan font lebih kecil). ketika di hover, diagramnya membuat shadow, bukan glow, jadi dia hover naik, dan bawahnya ada bayangan.
dan tadi pas klik diagramnya, menu detailnya itu belum tampil, coba perhatikan seedernya apakah ada bug, karena kakek dan nenek tidak tampil di diagramnya.
