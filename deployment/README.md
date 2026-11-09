# Deploy Scheduler HADIRRA

Scheduler menjalankan Laravel Scheduler setiap menit melalui systemd timer. Proses tetap berjalan saat browser ditutup atau pengguna logout dan otomatis aktif kembali setelah server restart.

## Prasyarat

- Linux dengan systemd.
- PHP CLI sesuai versi aplikasi.
- Dependensi Composer sudah terpasang.
- File `.env` sudah tersedia dan koneksi database berfungsi.
- Folder repository dimiliki user Linux non-root yang dapat menjalankan aplikasi.
- Migrasi database sudah dijalankan.

## Instalasi satu kali

Dari root folder repository:

```bash
php artisan migrate --force
sudo bash deployment/install-freedom-sync.sh
sudo bash deployment/install-queue-worker.sh
```

Installer mendeteksi folder repository, pemilik folder aplikasi, group, dan lokasi PHP CLI. Service dijalankan sebagai pemilik folder aplikasi, bukan root.

## Pengaturan dari aplikasi

Buka **Absensi → Log Mesin**, lalu centang **Aktifkan Sync Otomatis**.

- Aktif: scheduler menjalankan sinkronisasi mesin, pengguna, dan log setiap menit.
- Nonaktif: scheduler tetap hidup, tetapi perintah sinkronisasi dilewati.
- Pengaturan disimpan di database sehingga tidak bergantung pada browser atau sesi login.

Jangan menonaktifkan timer systemd untuk menghentikan sinkronisasi sehari-hari. Gunakan centang di halaman Log Mesin.

## Background Sync Data Pengguna Mesin

Fitur **Ambil Data dari Mesin** diproses melalui Laravel Queue dengan queue database.

- Browser hanya mengirim permintaan awal dan menampilkan progres. Browser tidak menjalankan proses sinkronisasi.
- Setiap pekerjaan dibatasi pada `cloud_id` mesin yang dipilih.
- Pengambilan daftar PIN dan pemeriksaan informasi pengguna/biometrik berjalan bertahap melalui job queue.
- Status dan progres disimpan pada tabel `machine_data_syncs`, sehingga halaman dapat ditutup tanpa menghentikan pekerjaan.
- Queue Worker dijalankan sebagai systemd service dan otomatis aktif kembali setelah VPS restart.
- Worker dijalankan sebagai pemilik folder aplikasi, bukan sebagai root.

Pasang Queue Worker satu kali menggunakan installer pada bagian Instalasi. Jangan menjalankan `queue:work` secara manual bersamaan dengan service systemd yang sama karena dapat menyebabkan worker ganda.

## Pemeriksaan Service

Queue Worker:

```bash
systemctl status hadirra-queue-worker.service
journalctl -u hadirra-queue-worker.service -f
```

Scheduler:

```bash
systemctl status hadirra-scheduler.timer
systemctl list-timers hadirra-scheduler.timer
systemctl status hadirra-scheduler.service
journalctl -u hadirra-scheduler.service -f
```

Untuk memeriksa status dan progres sinkronisasi per mesin melalui Laravel:

```bash
php artisan tinker --execute="dump(DB::table('machine_data_syncs')->orderByDesc('updated_at')->get()->toArray());"
```

Kolom penting:
- `cloud_id`: identitas mesin yang sedang diproses.
- `status`: status proses sinkronisasi.
- `total`: jumlah PIN yang akan diperiksa.
- `checked`: jumlah PIN yang sudah diperiksa.
- `unavailable`: jumlah PIN yang tidak tersedia.
- `pending`: jumlah PIN yang belum selesai diperiksa.
- `message`: keterangan proses.
- `started_at` dan `finished_at`: waktu mulai dan selesai.

Jika progres tidak bergerak, periksa status service dan log Queue Worker sebelum mengambil tindakan lain.

## Uninstall

```bash
sudo bash deployment/uninstall-freedom-sync.sh
```

Uninstaller menghapus unit systemd scheduler dan queue worker HADIRRA; tidak menghapus aplikasi atau database.

## Catatan deployment

- Jika sebelumnya ada Cron yang menjalankan `php artisan schedule:run`, hapus trigger Cron lama agar scheduler tidak berjalan ganda.
- Timer systemd memanggil `schedule:run` setiap menit. Definisi jadwal aplikasi tetap berada di `routes/console.php`.
- Pastikan timezone server dan `APP_TIMEZONE` aplikasi sudah sesuai.
- Installer tidak mengubah firewall, konfigurasi web server, file `.env`, atau permission folder aplikasi.
