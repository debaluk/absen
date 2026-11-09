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
- Menambahkan pencatatan status/progres pembaruan per mesin agar proses asinkron mengikuti siklus komunikasi mesin; template biometrik tidak disimpan.
- Memisahkan proses ini dari **Sinkronisasi** pada Log Mesin, tanpa mengubah fungsi sinkronisasi log absensi.
- Memindahkan proses **Ambil Data dari Mesin** ke Laravel Queue per `cloud_id`; tombol pada masing-masing kartu tetap menjadi pemicu proses dan pekerjaan berlanjut di backend sampai selesai tanpa bergantung pada tab browser.
- Menyimpan status dan progres setiap mesin pada tabel `machine_data_syncs`, serta menampilkan progres secara langsung pada kartu mesin tanpa popup.
- Menambahkan service worker Queue berbasis systemd agar antrean tetap diproses setelah browser ditutup atau server dimulai ulang.
- Mendokumentasikan instalasi, pemeriksaan service, log worker, dan pemeriksaan progres background sync pada `deployment/README.md`.

### Alur Pengambilan Data dan Pemeriksaan Biometrik
1. Admin menekan **Ambil Data dari Mesin** pada kartu mesin yang dipilih.
2. Aplikasi mengambil daftar pengguna mesin dan memperbarui machine_users berdasarkan cloud_id mesin tersebut.
3. Untuk setiap PIN, aplikasi meminta `get_userinfo` dan menyimpan `finger_count` setelah hasil diterima.
4. Proses berjalan bertahap mengikuti respons asinkron mesin sampai seluruh PIN selesai diproses.
5. Seluruh pembacaan dan pembaruan dibatasi pada `cloud_id` mesin yang dipilih; template biometrik tidak disimpan di MySQL.
6. Job Queue berjalan mandiri per mesin; progres disimpan di database dan ditampilkan inline pada kartu mesin. Browser boleh ditutup tanpa menghentikan pekerjaan selama Queue Worker aktif.

### Hasil
Pengelolaan Departemen/Jabatan, Ikut Rekap, dan penyaringan Rekap Absensi telah diterapkan serta migration terkait telah dijalankan. Alur biometrik disederhanakan dengan menghapus pembandingan timestamp cache dan finalisasi cache. Implementasi Queue dan tampilan progres per mesin telah ditambahkan. Validasi operasional di VPS menunjukkan service `hadirra-queue-worker.service` aktif dan job `ContinueMachineDataSync` diproses; pada pemeriksaan terakhir, progres tercatat 13 dari 99 PIN (status masih berjalan). Sinkronisasi sampai seluruh PIN selesai dan pengujian CI belum terverifikasi.

---

## Pedoman Penulisan
- Satu rangkaian pekerjaan dicatat dalam satu log.
- Gunakan poin singkat pada Resume Pekerjaan dan satu kalimat pada Hasil.
- Perubahan struktur database melalui migration dan perubahan logika aplikasi dicatat sebagai bagian dari pekerjaan terkait, bukan log terpisah.
- Gabungkan perbaikan kecil yang masih termasuk dalam pekerjaan yang sama.
- Catat hasil pengujian hanya jika benar-benar sudah dijalankan.
