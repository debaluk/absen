# Deploy Scheduler dan Background Worker HADIRRA

Dokumen ini menjelaskan instalasi dan prosedur operasional Scheduler serta Queue Worker pada server Linux yang menggunakan systemd.

- **Scheduler** menjalankan tugas terjadwal Laravel setiap menit.
- **Queue Worker** memproses pekerjaan latar belakang, termasuk sinkronisasi data pengguna mesin.
- Keduanya berjalan sebagai service systemd, tidak bergantung pada browser, dan dapat aktif kembali setelah VPS restart.

## Prasyarat

- Linux dengan systemd.
- PHP CLI sesuai versi aplikasi.
- Dependensi Composer sudah terpasang.
- File .env sudah tersedia dan koneksi database berfungsi.
- Folder repository dimiliki user Linux non-root yang dapat menjalankan aplikasi.
- Migration database sudah dijalankan.
- Queue connection menggunakan database, sesuai konfigurasi aplikasi.

## 1. Instalasi pada server baru

Jalankan perintah dari root folder repository. Pastikan aplikasi sudah di-deploy, file .env sudah benar, dan dependency Composer sudah tersedia.

~~~bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
sudo bash deployment/install-freedom-sync.sh
sudo bash deployment/install-queue-worker.sh
~~~

Jika asset frontend perlu dibangun pada instalasi tersebut, jalankan:

~~~bash
npm ci
npm run build
~~~

Installer Queue Worker:
- mendeteksi lokasi repository, pemilik folder aplikasi, group, dan PHP CLI;
- menolak menjalankan worker sebagai root;
- membuat service hadirra-queue-worker.service;
- mengaktifkan service agar berjalan sekarang dan saat VPS boot.

Jangan mengubah APP_KEY pada instalasi yang sudah berjalan. Jangan menjalankan installer berulang kali tanpa kebutuhan; jalankan kembali jika unit service memang perlu dibuat ulang atau diperbarui.

## 2. Deployment setelah git pull

Lakukan deployment dari root folder aplikasi. Pastikan perubahan yang akan dipasang sudah berada di branch main.

~~~bash
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
sudo systemctl restart hadirra-queue-worker.service
~~~

Jika perubahan menyentuh dependency atau asset frontend, jalankan juga:

~~~bash
npm ci
npm run build
~~~

Catatan:
- Jalankan migration setelah mengambil perubahan kode.
- Restart Queue Worker setelah deployment agar proses worker memuat kode terbaru.
- Jangan menjalankan queue:work manual bersamaan dengan service systemd.
- Jangan menghapus atau mengganti file .env, storage, vendor, maupun asset produksi secara sembarang.
- Jika file unit systemd berubah, jalankan sudo systemctl daemon-reload sebelum restart service.

## 3. Memastikan worker aktif

Periksa status Queue Worker:

~~~bash
systemctl status hadirra-queue-worker.service
~~~

Status normal ditandai dengan Active: active (running). Pastikan service juga enabled agar otomatis berjalan setelah VPS restart:

~~~bash
systemctl is-enabled hadirra-queue-worker.service
~~~

Nilai yang diharapkan adalah enabled.

Untuk memeriksa Scheduler:

~~~bash
systemctl status hadirra-scheduler.timer
systemctl list-timers hadirra-scheduler.timer
systemctl status hadirra-scheduler.service
~~~

Jika Queue Worker tidak aktif, lanjutkan ke pemeriksaan log pada bagian 4 sebelum melakukan restart.

## 4. Memantau proses sinkronisasi

Lihat log Queue Worker secara langsung:

~~~bash
journalctl -u hadirra-queue-worker.service -f
~~~

Untuk melihat riwayat log terbaru:

~~~bash
journalctl -u hadirra-queue-worker.service -n 100 --no-pager
~~~

Periksa progres sinkronisasi yang tersimpan di database:

~~~bash
php artisan tinker --execute="dump(DB::table('machine_data_syncs')->orderByDesc('updated_at')->get()->toArray());"
~~~

Kolom yang perlu diperhatikan:

| Kolom | Arti |
|---|---|
| cloud_id | Identitas mesin yang diproses |
| status | Status proses sinkronisasi |
| total | Jumlah PIN yang akan diperiksa |
| checked | Jumlah PIN yang sudah diperiksa |
| unavailable | Jumlah PIN yang tidak tersedia |
| pending | Jumlah PIN yang belum selesai diperiksa |
| message | Keterangan proses |
| started_at | Waktu proses dimulai |
| finished_at | Waktu proses selesai |

Setiap sinkronisasi dibatasi pada cloud_id mesin yang dipilih. Browser hanya memulai permintaan dan menampilkan progres; pekerjaan tetap diproses oleh Queue Worker walaupun halaman ditutup atau pengguna logout.

## 5. Menangani proses macet atau gagal

Lakukan pemeriksaan berurutan agar penyebab masalah diketahui sebelum tindakan dilakukan.

**A. Periksa service dan log**

~~~bash
systemctl status hadirra-queue-worker.service
journalctl -u hadirra-queue-worker.service -n 200 --no-pager
~~~

Perhatikan error koneksi database, exception aplikasi, timeout, atau error komunikasi dengan Freedom Finger.

**B. Periksa progres sinkronisasi**

~~~bash
php artisan tinker --execute="dump(DB::table('machine_data_syncs')->orderByDesc('updated_at')->limit(10)->get()->toArray());"
~~~

Bandingkan updated_at, checked, pending, dan message. Proses patut diperiksa lebih lanjut jika progres tidak berubah dalam waktu yang tidak wajar dan worker tidak sedang memproses pekerjaan.

**C. Periksa pekerjaan yang gagal**

Jalankan sebagai user pemilik folder aplikasi:

~~~bash
php artisan queue:failed
~~~

Baca pesan exception dan perbaiki penyebabnya terlebih dahulu. Jika pekerjaan tercatat gagal dan penyebab sudah ditangani, ulangi hanya pekerjaan yang diperlukan:

~~~bash
php artisan queue:retry ID_JOB
~~~

Ganti ID_JOB dengan ID yang ditampilkan oleh queue:failed. Jangan menjalankan queue:retry all tanpa memeriksa dampaknya.

**D. Restart worker jika diperlukan**

Setelah penyebab masalah diperiksa atau diperbaiki:

~~~bash
sudo systemctl restart hadirra-queue-worker.service
systemctl status hadirra-queue-worker.service
~~~

Lanjutkan pemantauan log dan progres. Restart worker tidak otomatis memperbaiki masalah koneksi, data, atau exception aplikasi yang menjadi penyebab awal.

Jangan menghapus isi tabel jobs, failed_jobs, atau machine_data_syncs secara manual untuk mengatasi proses macet. Jangan menghapus record progres sebelum status dan pekerjaan terkait diperiksa.

## 6. Memastikan sinkronisasi benar-benar selesai

Periksa record sinkronisasi terbaru untuk cloud_id mesin yang dimaksud:

~~~bash
php artisan tinker --execute="dump(DB::table('machine_data_syncs')->where('cloud_id', 'ISI_CLOUD_ID')->orderByDesc('updated_at')->first());"
~~~

Ganti ISI_CLOUD_ID dengan identitas mesin yang diperiksa.

Sinkronisasi dapat dinyatakan selesai jika seluruh kondisi berikut terpenuhi:

1. checked sudah sama dengan total.
2. pending bernilai 0.
3. finished_at sudah terisi.
4. status sudah berubah ke status akhir, bukan masih berjalan.
5. Tidak ada error yang belum ditindaklanjuti pada log atau daftar failed jobs.

Periksa juga nilai unavailable. Nilai tersebut menunjukkan PIN yang tidak tersedia dan harus dipahami sebagai hasil pemeriksaan, bukan otomatis dianggap berhasil diambil. Jika jumlahnya bukan nol, tinjau pesan dan log terkait sebelum menyimpulkan seluruh data pengguna mesin telah lengkap.

Tampilan browser bukan satu-satunya bukti penyelesaian. Gunakan record machine_data_syncs dan log worker sebagai dasar verifikasi.

## Pengaturan Sync Otomatis

Buka **Absensi → Log Mesin**, lalu centang **Aktifkan Sync Otomatis**.

- Aktif: scheduler menjalankan sinkronisasi mesin, pengguna, dan log sesuai jadwal aplikasi.
- Nonaktif: scheduler tetap hidup, tetapi perintah sinkronisasi otomatis dilewati.
- Pengaturan disimpan di database sehingga tidak bergantung pada browser atau sesi login.

Jangan menonaktifkan timer systemd untuk menghentikan sinkronisasi sehari-hari. Gunakan pengaturan di halaman Log Mesin.

## Uninstall

Untuk menghapus unit systemd scheduler dan queue worker HADIRRA:

~~~bash
sudo bash deployment/uninstall-freedom-sync.sh
~~~

Uninstaller tidak menghapus aplikasi atau database. Pastikan memahami dampaknya sebelum menjalankan perintah ini.

## Catatan operasional

- Jika sebelumnya ada Cron yang menjalankan php artisan schedule:run, hapus trigger Cron lama agar scheduler tidak berjalan ganda.
- Timer systemd memanggil schedule:run setiap menit. Definisi jadwal aplikasi tetap berada di routes/console.php.
- Pastikan timezone server dan APP_TIMEZONE aplikasi sudah sesuai.
- Installer tidak mengubah firewall, konfigurasi web server, file .env, atau permission folder aplikasi.
