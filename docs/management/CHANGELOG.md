# CHANGELOG — Fingerspot Management

Catatan ringkas pekerjaan dan hasil penting proyek. Satu rangkaian pekerjaan dicatat dalam satu log.

## LOG-004 — Filter Departemen dan Export Excel Rekap Kehadiran
**Tanggal:** 2026-11-09

### Resume Pekerjaan
- Menambahkan filter Departemen pada Rekap Kehadiran melalui relasi Jabatan–Departemen.
- Menambahkan Export Excel dengan kop perusahaan, judul rekap, bulan, Departemen, dan Lokasi Kerja.
- Menyamakan kolom export dengan seluruh kolom yang tampil pada tabel Rekap Kehadiran.

### Hasil
Filter Departemen menyaring data rekap, sedangkan Export Excel menghasilkan file .xls sesuai filter aktif dan susunan kolom tabel; pengujian aplikasi belum dilakukan.

---

## LOG-003 — Filter Departemen pada Dashboard
**Tanggal:** 2026-11-09

### Resume Pekerjaan
- Menambahkan filter Departemen pada header Dashboard.
- Menerapkan filter ke KPI karyawan, ringkasan kehadiran, jumlah scan, rekap karyawan per lokasi kerja, dan aktivitas scan terbaru.
- Menggunakan relasi Jabatan–Departemen melalui `department_positions` tanpa menambah join yang dapat menggandakan baris karyawan.
- Panel mesin tetap menampilkan perangkat berdasarkan filter Lokasi Kerja karena data mesin tidak memiliki pemetaan Departemen.

### Hasil
Dashboard dapat menampilkan ringkasan karyawan dan aktivitas absensi berdasarkan Departemen yang dipilih tanpa mengubah relasi atau data mesin.

- Template Import Data Karyawan tidak lagi memuat PIN/ID Mesin. Proses import hanya menyimpan data karyawan dan lokasi kerja; penautan mesin dikelola terpisah.

- Import Data Karyawan mendukung banyak baris dalam satu file Excel. Setiap baris diproses dalam transaksi tersendiri; kegagalan satu baris tidak membatalkan baris lain. Progress menampilkan jumlah baris diproses serta ringkasan berhasil/gagal dan alasan kegagalan.

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
- Menambahkan filter gabungan pada daftar Karyawan dengan urutan Lokasi Kerja, Departemen, Reg Mesin, Status Aktif, dan pencarian nama/kode karyawan.
- Menyederhanakan filter Reg Mesin menjadi **Sudah Terdaftar** dan **Belum Terdaftar**; status belum terdaftar mencakup PIN yang belum diketahui maupun yang belum memiliki sidik jari.
- Menambahkan Export Excel yang mengikuti seluruh filter aktif, termasuk pencarian karyawan, serta menyertakan informasi perusahaan, metadata filter, dan kolom data karyawan.
- Menambahkan field opsional Alamat, No. HP/Telp, Tempat Lahir, dan Tanggal Lahir pada data/form Karyawan untuk melengkapi kebutuhan export; field tersebut tidak diwajibkan.
- Menambahkan popup **Import Karyawan** dengan petunjuk, pilihan Lokasi Kerja, upload file Excel, download template, progress bar, dan informasi hasil **Berhasil** atau **Dibatalkan**.
- Menyatukan logika dan tampilan Import ke `app/Filament/Pages/Employees.php` dan `resources/views/filament/pages/employees.blade.php`; tidak menggunakan komponen Livewire Import terpisah.
- Memperbaiki sintaks regex pembacaan file Excel yang menyebabkan PHP ParseError pada komponen Import sebelumnya.
- Membatasi satu file untuk satu karyawan; kode karyawan dibuat otomatis dengan melanjutkan pola kode terakhir yang memiliki akhiran angka (menggunakan pola `EMP000001` jika belum ada kode sebelumnya).
- Memvalidasi NIK dan nama terhadap data yang sudah ada, memastikan PIN dan ID Mesin terdaftar pada `machine_users`, serta membatalkan import jika PIN sudah digunakan pada lokasi mesin yang sama.
- Memisahkan Lokasi Kerja hasil import dari lokasi mesin; lokasi kerja berasal dari pilihan pada popup, sedangkan lokasi mesin hanya digunakan untuk validasi duplikasi PIN.

### Alur Pengambilan Data dan Pemeriksaan Biometrik
1. Admin menekan **Ambil Data dari Mesin** pada kartu mesin yang dipilih.
2. Aplikasi mengambil daftar pengguna mesin dan memperbarui machine_users berdasarkan cloud_id mesin tersebut.
3. Untuk setiap PIN, aplikasi meminta `get_userinfo` dan menyimpan `finger_count` setelah hasil diterima.
4. Proses berjalan bertahap mengikuti respons asinkron mesin sampai seluruh PIN selesai diproses.
5. Seluruh pembacaan dan pembaruan dibatasi pada `cloud_id` mesin yang dipilih; template biometrik tidak disimpan di MySQL.
6. Job Queue berjalan mandiri per mesin; progres disimpan di database dan ditampilkan inline pada kartu mesin. Browser boleh ditutup tanpa menghentikan pekerjaan selama Queue Worker aktif.

### Hasil
Pengelolaan Departemen/Jabatan, Ikut Rekap, penyaringan Rekap Absensi, filter daftar Karyawan, Export Excel, dan popup Import Karyawan telah diterapkan. Alur biometrik disederhanakan dengan menghapus pembandingan timestamp cache dan finalisasi cache. Implementasi Queue dan tampilan progres per mesin telah ditambahkan. Validasi operasional di VPS menunjukkan service `hadirra-queue-worker.service` aktif dan job `ContinueMachineDataSync` diproses; pada pemeriksaan terakhir, progres tercatat 13 dari 99 PIN (status masih berjalan). Sinkronisasi sampai seluruh PIN selesai, pengujian CI, pengujian menyeluruh filter/export, serta pengujian operasional Import Karyawan belum terverifikasi.

---

## Pedoman Penulisan
- Satu rangkaian pekerjaan dicatat dalam satu log.
- Gunakan poin singkat pada Resume Pekerjaan dan satu kalimat pada Hasil.
- Perubahan struktur database melalui migration dan perubahan logika aplikasi dicatat sebagai bagian dari pekerjaan terkait, bukan log terpisah.
- Gabungkan perbaikan kecil yang masih termasuk dalam pekerjaan yang sama.
- Catat hasil pengujian hanya jika benar-benar sudah dijalankan.
