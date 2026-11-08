# CHANGELOG — Fingerspot Management

Catatan ringkas pekerjaan dan hasil penting proyek. Satu rangkaian pekerjaan dicatat dalam satu log.

## LOG-001 — Tata Kelola Dokumentasi
**Tanggal:** 2026-11-08

### Resume Pekerjaan
- Menetapkan Master Plan sebagai acuan utama rancangan dan keputusan proyek.
- Menetapkan Change Log sebagai catatan ringkas riwayat pekerjaan.

### Hasil
Dokumentasi proyek memiliki acuan utama dan riwayat pekerjaan yang mudah ditelusuri.

## LOG-002 — Pengembangan Menu Karyawan, Rekap Absensi, dan Data Mesin
**Tanggal:** 2026-11-09

### Resume Pekerjaan
- Menambahkan pengelolaan Departemen dan pemetaan Jabatan serta pengaturan Ikut Rekap pada daftar Karyawan.
- Menyesuaikan Rekap Absensi agar hanya menghitung karyawan Aktif dan Ikut Rekap = Ya, serta mencatat kebutuhan filter Departemen, import/export, dan optimalisasi data absensi dalam rangkaian pengembangan ini.
- Melakukan pemeriksaan biometrik melalui `get_userinfo` Freedom Finger untuk memperoleh jumlah sidik jari tanpa menyimpan template biometrik.
- Mengaitkan status registrasi biometrik dari data `machine_users` ke data Karyawan melalui relasi PIN dan `cloud_id`, serta menampilkan informasinya pada kolom Reg. Mesin.
- Menambahkan metadata hasil pemeriksaan dan mekanisme sinkronisasi berkala agar status registrasi biometrik dapat diperbarui.
- Menambahkan tombol **Ambil Data dari Mesin** pada setiap kartu mesin di Pengaturan → Mesin, sehingga pembaruan `machine_users` ditujukan berdasarkan `cloud_id` mesin yang dipilih.
- Mengambil daftar PIN langsung dari mesin melalui `get_all_pin`, kemudian memperbarui identitas dan jumlah sidik jari setiap PIN melalui `get_userinfo` secara bertahap.
- Menambahkan pencatatan status/progres pembaruan per mesin dan worker terjadwal agar proses asinkron mengikuti siklus komunikasi mesin; template biometrik tidak disimpan.
- Memisahkan proses ini dari **Sinkronisasi** pada Log Mesin, tanpa mengubah fungsi sinkronisasi log absensi.

### Hasil
Pengelolaan Departemen/Jabatan, Ikut Rekap, dan penyaringan Rekap Absensi telah diterapkan serta diuji termasuk migration; tombol pembaruan data pengguna per mesin beserta proses pemeriksaan PIN sudah ditambahkan ke kode, tetapi migration baru dan pengujian langsung ke mesin masih perlu dijalankan.

---

## Pedoman Penulisan
- Satu rangkaian pekerjaan dicatat dalam satu log.
- Gunakan poin singkat pada Resume Pekerjaan dan satu kalimat pada Hasil.
- Perubahan struktur database melalui migration dan perubahan logika aplikasi dicatat sebagai bagian dari pekerjaan terkait, bukan log terpisah.
- Gabungkan perbaikan kecil yang masih termasuk dalam pekerjaan yang sama.
- Catat hasil pengujian hanya jika benar-benar sudah dijalankan.
