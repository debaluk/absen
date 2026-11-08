# CHANGELOG — Fingerspot Management

Catatan ringkas pekerjaan dan hasil penting proyek. Satu rangkaian pekerjaan dicatat dalam satu log.

## LOG-001 — Tata Kelola Dokumentasi
**Tanggal:** 2026-11-08

### Resume Pekerjaan
- Menetapkan Master Plan sebagai acuan utama rancangan dan keputusan proyek.
- Menetapkan Change Log sebagai catatan ringkas riwayat pekerjaan.

### Hasil
Dokumentasi proyek memiliki acuan utama dan riwayat pekerjaan yang mudah ditelusuri.

## LOG-002 — Pengembangan Menu Karyawan dan Rekap Absensi
**Tanggal:** 2026-11-09

### Resume Pekerjaan
- Menambahkan pengelolaan Departemen dan pemetaan Jabatan serta pengaturan Ikut Rekap pada daftar Karyawan.
- Menyesuaikan Rekap Absensi agar hanya menghitung karyawan Aktif dan Ikut Rekap = Ya, serta mencatat kebutuhan filter Departemen, import/export, dan optimalisasi data absensi dalam rangkaian pengembangan ini.
- Menambahkan metadata jumlah sidik jari dan status pemeriksaannya pada `machine_users`, beserta sinkronisasi berkala melalui perintah `get_userinfo` Freedom Finger tanpa menyimpan template biometrik.
- Menampilkan kolom Reg. Mesin pada daftar Karyawan; mencatat rencana tombol **Perbarui Reg. Mesin** agar pemeriksaan dapat dipicu manual tanpa menunggu sinkronisasi berkala. Tombol belum diimplementasikan.

### Hasil
Pengelolaan Departemen/Jabatan, pengaturan Ikut Rekap, dan penyaringan Rekap Absensi telah diterapkan serta diuji langsung termasuk migration database; perubahan metadata dan tampilan Reg. Mesin sudah dicatat dalam kode, tetapi masih memerlukan penerapan migration dan verifikasi pada lingkungan aplikasi.

---

## Pedoman Penulisan
- Satu rangkaian pekerjaan dicatat dalam satu log.
- Gunakan poin singkat pada Resume Pekerjaan dan satu kalimat pada Hasil.
- Perubahan struktur database melalui migration dan perubahan logika aplikasi dicatat sebagai bagian dari pekerjaan terkait, bukan log terpisah.
- Gabungkan perbaikan kecil yang masih termasuk dalam pekerjaan yang sama.
- Catat hasil pengujian hanya jika benar-benar sudah dijalankan.
