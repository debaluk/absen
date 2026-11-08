# CHANGELOG — Fingerspot Management

Catatan ringkas pekerjaan dan hasil penting proyek. Satu rangkaian pekerjaan dicatat dalam satu log.

## LOG-001 — Tata Kelola Dokumentasi
**Tanggal:** 2026-11-08

### Resume Pekerjaan
- Menetapkan Master Plan sebagai acuan utama rancangan dan keputusan proyek.
- Menetapkan Change Log sebagai catatan ringkas riwayat pekerjaan.

### Hasil
Dokumentasi proyek memiliki acuan utama dan riwayat pekerjaan yang mudah ditelusuri.

## LOG-002 — Pengembangan Departemen dan Rekap Karyawan
**Tanggal:** 2026-11-09

### Resume Pekerjaan
- Menambahkan pengelolaan Departemen dan pemetaan Jabatan.
- Menambahkan pilihan Ikut Rekap pada daftar Karyawan.
- Menyesuaikan Rekap Absensi agar hanya menghitung karyawan Aktif dan Ikut Rekap = Ya.

### Hasil
Pengelolaan departemen dan penyaringan karyawan pada rekap absensi sudah diterapkan dan diuji langsung, termasuk migration database.

## LOG-003 — Status Registrasi Mesin pada Daftar Karyawan
**Tanggal:** 2026-11-09

### Resume Pekerjaan
- Menambahkan metadata jumlah sidik jari dan status pemeriksaannya pada `machine_users`.
- Menambahkan sinkronisasi berkala melalui perintah `get_userinfo` Freedom Finger tanpa menyimpan template biometrik.
- Menampilkan kolom Reg. Mesin pada daftar Karyawan dengan status belum diketahui jika data belum berhasil diperiksa.

### Hasil
Daftar Karyawan dapat menampilkan jumlah sidik jari berdasarkan hasil pemeriksaan mesin.

---

## Pedoman Penulisan
- Satu rangkaian pekerjaan dicatat dalam satu log.
- Gunakan poin singkat pada Resume Pekerjaan dan satu kalimat pada Hasil.
- Perubahan struktur database melalui migration dan perubahan logika aplikasi dicatat sebagai bagian dari pekerjaan terkait, bukan log terpisah.
- Gabungkan perbaikan kecil yang masih termasuk dalam pekerjaan yang sama.
- Catat hasil pengujian hanya jika benar-benar sudah dijalankan.
