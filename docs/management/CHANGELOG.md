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
- Menyesuaikan Rekap Absensi agar hanya menghitung karyawan Aktif dan Ikut Rekap = Ya, serta mencatat kebutuhan optimalisasi data absensi dalam rangkaian pengembangan ini.
- Menambahkan filter Departemen pada header Dashboard dan menerapkannya ke KPI karyawan, ringkasan kehadiran, jumlah scan, rekap karyawan per lokasi kerja, dan aktivitas scan terbaru.
- Menggunakan relasi Jabatan–Departemen melalui `department_positions` tanpa menambah join yang dapat menggandakan baris karyawan. Panel mesin tetap menampilkan perangkat berdasarkan filter Lokasi Kerja karena data mesin tidak memiliki pemetaan Departemen.
- Menambahkan filter Departemen pada Rekap Kehadiran dengan relasi Jabatan–Departemen melalui `department_positions` dan subquery `whereExists`.
- Menambahkan indikator loading saat filter Rekap Kehadiran diproses, termasuk overlay pada tabel.
- Menambahkan Export Excel Rekap Kehadiran dalam format `.xls`, berisi kop perusahaan, judul rekap, informasi Bulan, Departemen, dan Lokasi Kerja, serta kolom yang mengikuti tabel.
- Memperjelas tombol export menjadi **Unduh Excel** dengan background hijau, teks putih, ikon, dan indikator proses export.
- Template Import Data Karyawan tidak lagi memuat PIN/ID Mesin. Proses import hanya menyimpan data karyawan dan lokasi kerja; penautan mesin dikelola terpisah.
- Import Data Karyawan mendukung banyak baris dalam satu file Excel. Setiap baris diproses dalam transaksi tersendiri; kegagalan satu baris tidak membatalkan baris lain. Progress menampilkan jumlah baris diproses serta ringkasan berhasil/gagal dan alasan kegagalan.
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

### Status
Closed

### Hasil
Pengembangan Menu Karyawan, Rekap Absensi, Rekap Kehadiran, Dashboard, Import/Export Karyawan, serta pengelolaan data mesin dan pemeriksaan biometrik telah dicatat dalam rangkaian pekerjaan ini. Implementasi Queue dan tampilan progres pengambilan data per mesin juga telah ditambahkan.

---

## LOG-003 — Rancangan Formula Performa Kehadiran
**Tanggal:** 2026-11-10

### Resume Pekerjaan
- Menambahkan dokumen rancangan Formula Performa Kehadiran pada `docs/management/RANCANGAN_FORMULA_PERFORMA_KEHADIRAN.md`.
- Mendokumentasikan indikator, definisi status, rumus dan simulasi perhitungan, input konfigurasi, bobot usulan, kategori hasil, role/permission, rancangan laporan, validasi, kasus khusus, risiko, serta keputusan yang masih terbuka.
- Menegaskan bahwa bobot dan parameter contoh masih berupa usulan dan belum menjadi keputusan implementasi.

### Hasil
Rancangan Formula Performa Kehadiran tersedia sebagai dokumen pembahasan dan belum mengubah implementasi aplikasi.


## LOG-004 — Implementasi Awal Formula Performa
**Tanggal:** 2026-11-10

### Resume Pekerjaan
- Menambahkan migration tabel `attendance_performance_formulas` untuk menyimpan konfigurasi formula, masa berlaku, status, dan riwayat pembuat/perubah.
- Menambahkan halaman **Formula Performa** pada group **Kelola**.
- Menyediakan konfigurasi bobot kehadiran, ketepatan waktu, kepatuhan jam kerja, toleransi, pengurangan berbasis menit, dan kategori nilai.
- Menyediakan status Draft, Berlaku, dan Berakhir. Formula tanpa tanggal akhir tetap berlaku sampai diakhiri atau digantikan oleh versi berikutnya.
- Saat formula baru diaktifkan, formula aktif sebelumnya yang bertabrakan diakhiri sehari sebelum tanggal mulai formula baru.
- Menampilkan aturan izin, sakit, dinas luar, ALPA, cakupan perusahaan, dan periode bulanan sebagai ketentuan tetap yang telah disepakati.
- Menandai nilai awal sebagai contoh dan mensyaratkan konfirmasi sebelum aktivasi.

### Status
Implementasi awal tersedia di branch `main`. Migration dan halaman belum dijalankan pada lingkungan aplikasi; verifikasi runtime masih diperlukan.

### Hasil
Halaman pengaturan Formula Performa dan penyimpanan versinya telah ditambahkan. Perhitungan nilai dan laporan performa belum termasuk dalam pekerjaan ini.

---

## Pedoman Penulisan
- Satu rangkaian pekerjaan dicatat dalam satu log.
- Gunakan poin singkat pada Resume Pekerjaan dan satu kalimat pada Hasil.
- Perubahan struktur database melalui migration dan perubahan logika aplikasi dicatat sebagai bagian dari pekerjaan terkait, bukan log terpisah.
- Gabungkan perbaikan kecil yang masih termasuk dalam pekerjaan yang sama.
- Catat hasil pengujian hanya jika benar-benar sudah dijalankan.
