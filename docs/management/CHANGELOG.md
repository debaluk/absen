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
- Melakukan pemeriksaan biometrik melalui `get_userinfo` Freedom Finger untuk memperoleh jumlah sidik jari tanpa menyimpan template biometrik.
- Mengaitkan status registrasi biometrik dari data `machine_users` ke data Karyawan melalui relasi PIN dan `cloud_id`, serta menampilkan informasinya pada kolom Reg. Mesin.
- Menambahkan metadata hasil pemeriksaan dan mekanisme sinkronisasi berkala agar status registrasi biometrik dapat diperbarui.

### Hasil
Pengembangan menu Karyawan dan Rekap Absensi mencakup pengelolaan Departemen/Jabatan, pengaturan Ikut Rekap, pemeriksaan biometrik, serta pengaitan status registrasi biometrik ke data Karyawan; verifikasi penerapan migration dan pengujian pada lingkungan aplikasi tetap mengikuti pelaksanaan aktual.

---

## Pedoman Penulisan
- Satu rangkaian pekerjaan dicatat dalam satu log.
- Gunakan poin singkat pada Resume Pekerjaan dan satu kalimat pada Hasil.
- Perubahan struktur database melalui migration dan perubahan logika aplikasi dicatat sebagai bagian dari pekerjaan terkait, bukan log terpisah.
- Gabungkan perbaikan kecil yang masih termasuk dalam pekerjaan yang sama.
- Catat hasil pengujian hanya jika benar-benar sudah dijalankan.
