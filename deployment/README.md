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

## Pemeriksaan

```bash
systemctl status hadirra-queue-worker.service
journalctl -u hadirra-queue-worker.service -f

systemctl status hadirra-scheduler.timer
systemctl list-timers hadirra-scheduler.timer
systemctl status hadirra-scheduler.service
journalctl -u hadirra-scheduler.service -f
```

## Queue Worker

Pengambilan data pengguna mesin dijalankan melalui Laravel Queue. Pasang worker satu kali dengan installer di atas. Worker berjalan sebagai user pemilik aplikasi dan otomatis aktif kembali setelah server restart.

Pemeriksaan:

```bash
systemctl status hadirra-queue-worker.service
journalctl -u hadirra-queue-worker.service -f
```

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
