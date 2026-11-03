# HADIRRA — Fingerspot Management

Aplikasi web untuk pengelolaan karyawan, lokasi kerja, mesin absensi, dan data kehadiran secara terpusat. Sistem dirancang untuk satu perusahaan dengan banyak cabang/lokasi kerja dan banyak mesin absensi.

> **Status proyek:** dalam pengembangan aktif. Dokumentasi ini mengikuti struktur kode dan konfigurasi repository saat ini. Fitur atau proses yang belum selesai tidak dianggap sebagai kemampuan produksi.

## Daftar Isi

- [Gambaran Sistem](#gambaran-sistem)
- [Teknologi](#teknologi)
- [Arsitektur dan Integrasi](#arsitektur-dan-integrasi)
- [Struktur Data](#struktur-data)
- [Aturan Bisnis Absensi](#aturan-bisnis-absensi)
- [Persyaratan Instalasi](#persyaratan-instalasi)
- [Instalasi Lokal](#instalasi-lokal)
- [Instalasi Server Produksi](#instalasi-server-produksi)
- [Konfigurasi Freedom Finger](#konfigurasi-freedom-finger)
- [Pemeriksaan Setelah Instalasi](#pemeriksaan-setelah-instalasi)
- [Pemeliharaan dan Troubleshooting](#pemeliharaan-dan-troubleshooting)
- [Status Pengembangan](#status-pengembangan)

## Gambaran Sistem

HADIRRA mengelola data kehadiran karyawan dari mesin absensi melalui Freedom Finger. Aplikasi web menjadi pusat pengelolaan data perusahaan, cabang/lokasi kerja, karyawan, konfigurasi absensi, dan riwayat kehadiran.

Prinsip desain utama:

- Satu perusahaan dapat memiliki banyak lokasi kerja/cabang.
- Data karyawan dikelola secara terpusat.
- Mesin absensi dikelola secara terpusat dan dapat ditempatkan di lokasi kerja berbeda.
- Karyawan dihubungkan dengan identitas pengguna pada mesin.
- Data scan mentah disimpan sebagai catatan sumber dan tidak ditimpa oleh koreksi kehadiran.
- Kewajiban absensi mengikuti tanggal efektif yang ditetapkan, bukan semata-mata tanggal mulai bekerja.

## Teknologi

Versi kebutuhan utama berdasarkan konfigurasi repository:

| Komponen | Versi / Teknologi |
|---|---|
| Backend | Laravel `^13.17` |
| PHP | `^8.3` |
| Panel administrasi | Filament `^5.0` |
| Database | MySQL |
| Frontend build | Vite 8 |
| CSS | Tailwind CSS 4 |
| Integrasi mesin | Freedom Finger |

Versi dependency aktual mengikuti `composer.lock` dan `package-lock.json` pada repository.

## Arsitektur dan Integrasi

Komponen sistem dipisahkan menjadi:

1. **Aplikasi Laravel** — antarmuka administrasi dan pengelolaan data.
2. **Database MySQL** — menyimpan master perusahaan, karyawan, perangkat, log scan, jadwal, izin, dan koreksi.
3. **Freedom Finger** — layanan/engine komunikasi antara aplikasi dan mesin absensi.
4. **Mesin absensi** — mengirimkan data kehadiran ke Freedom Finger sesuai konfigurasi perangkat dan jaringan.

### Alur sinkronisasi log

Implementasi sinkronisasi pada `FreedomFingerAttendanceLogSynchronizer` melakukan langkah berikut:

1. Membaca daftar perangkat dari tabel `devices`.
2. Meminta Freedom Finger menarik log dari memori setiap mesin melalui endpoint `/api/sync_attlog`.
3. Memeriksa hasil perintah melalui `/api/get_result`.
4. Mengambil data log berdasarkan rentang tanggal melalui `/api/get_attlog`.
5. Menyimpan atau memperbarui log ke tabel `logs` berdasarkan kombinasi `cloud_id`, `pin`, dan `scan_date`.

Alur pengolahan log menjadi hasil kehadiran harian dan rekap masih mengikuti pengembangan modul absensi. Jangan menganggap tabel atau proses agregasi harian tertentu sudah tersedia sebelum ada implementasi dan migration-nya.

## Struktur Data

Migration Laravel merupakan sumber struktur database yang digunakan aplikasi saat ini. Kelompok tabel utama meliputi:

- **Organisasi:** perusahaan, lokasi kerja, dan jabatan.
- **Karyawan:** identitas karyawan, status, tanggal mulai bekerja, dan relasi penempatan.
- **Perangkat:** mesin, pengguna mesin, log scan, dan perintah perangkat.
- **Pengaturan absensi:** jadwal kerja, hari libur, izin, dan koreksi absensi.
- **Aplikasi:** pengguna, sesi, cache, antrean, dan pengaturan aplikasi.

Beberapa nama tabel penting:

| Tabel | Fungsi |
|---|---|
| `companies` | Data perusahaan |
| `work_locations` | Cabang/lokasi kerja |
| `positions` | Struktur jabatan |
| `employees` | Data karyawan |
| `employee_branch_assignments` | Riwayat penempatan karyawan pada cabang |
| `devices` | Identitas mesin berdasarkan `cloud_id` |
| `machine_users` | Pengguna/PIN yang terdaftar pada mesin |
| `logs` | Data scan mentah dari mesin |
| `cmds` | Perintah dan hasil komunikasi dengan mesin |
| `schedules` | Konfigurasi jadwal kerja |
| `work_schedule_settings` | Pengaturan kerja |
| `holidays` | Hari libur |
| `leaves` | Izin, sakit, cuti, dan dinas |
| `attendance_corrections` | Koreksi jam masuk/pulang |

> **Catatan database:** file rancangan SQL terpisah yang pernah disusun berisi 38 tabel. Jumlah dan struktur tersebut tidak boleh langsung dianggap identik dengan seluruh migration Laravel pada branch ini. Untuk instalasi aplikasi, gunakan migration repository. Jangan menjalankan file SQL rancangan bersamaan dengan migration pada database yang sama tanpa proses rekonsiliasi skema terlebih dahulu.

## Aturan Bisnis Absensi

### Tanggal karyawan

Tanggal berikut memiliki arti berbeda dan tidak boleh dipertukarkan:

- `hire_date`: tanggal resmi mulai bekerja di perusahaan.
- `start_date`: tanggal mulai penempatan karyawan pada cabang.
- `attendance_start_date`: tanggal mulai kewajiban absensi pada penempatan tersebut.

Tanggal mulai kewajiban absensi tidak boleh mendahului tanggal mulai penempatan atau melewati tanggal akhir penempatan.

### Penetapan ALPA

Karyawan tidak boleh otomatis dianggap ALPA hanya karena tidak ditemukan scan. Penetapan ALPA harus mempertimbangkan sekurang-kurangnya:

- tanggal kewajiban absensi sudah berlaku;
- karyawan memiliki jadwal kerja aktif pada tanggal tersebut;
- tidak terdapat catatan kehadiran yang memenuhi ketentuan; dan
- tidak terdapat izin yang disetujui atau kondisi pengecualian yang berlaku.

Jika tidak ada jadwal kerja aktif, sistem tidak boleh otomatis menetapkan ALPA.

### Data scan dan koreksi

- Log mesin merupakan data scan sumber.
- Koreksi jam masuk/pulang disimpan terpisah pada `attendance_corrections`.
- Koreksi tidak mengubah atau menghapus data scan mentah.

## Persyaratan Instalasi

Siapkan server atau komputer dengan:

- Linux server (Ubuntu direkomendasikan untuk deployment).
- PHP 8.3 atau lebih baru yang kompatibel dengan Laravel 13, beserta ekstensi PHP yang dibutuhkan Laravel dan Filament.
- Composer.
- MySQL yang kompatibel dengan migration aplikasi.
- Node.js dan npm yang kompatibel dengan Vite 8.
- Web server Nginx atau Apache untuk produksi.
- Git.
- Freedom Finger yang dapat dijangkau oleh server Laravel.

Pastikan versi dan ekstensi PHP diverifikasi sebelum instalasi. Jangan menyalin konfigurasi produksi ke repository.

## Instalasi Lokal

### 1. Clone repository

```bash
git clone https://github.com/debaluk/absen.git
cd absen
```

### 2. Siapkan database

Buat database MySQL kosong, misalnya:

```sql
CREATE DATABASE absen
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Buat user database khusus aplikasi dan berikan hak akses hanya pada database tersebut.

### 3. Siapkan environment

```bash
cp .env.example .env
```

Edit file `.env` dan sesuaikan setidaknya:

```dotenv
APP_NAME=HADIRRA
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

ADMIN_NAME="Administrator"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD="GANTI_DENGAN_PASSWORD_KUAT"

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absen
DB_USERNAME=USER_DATABASE
DB_PASSWORD="PASSWORD_DATABASE"

FREEDOM_FINGER_URL=http://127.0.0.1:8013
FREEDOM_FINGER_TOKEN=TOKEN_FREEDOM_FINGER
```

Gunakan password dan token milik instalasi sendiri. Jangan commit file `.env` atau membagikan kredensial.

### 4. Install dependency PHP

```bash
composer install
php artisan key:generate
```

### 5. Jalankan migration dan seeder

```bash
php artisan migrate --seed
```

Seeder utama menjalankan pengaturan jadwal kerja dan pembuatan akun administrator. `ADMIN_PASSWORD` wajib diisi sebelum seeder dijalankan.

Seeder administrator menggunakan `ADMIN_EMAIL`, `ADMIN_NAME`, dan `ADMIN_PASSWORD`. Jika akun dengan email tersebut sudah ada, seeder tidak membuat akun duplikat.

### 6. Install dependency frontend dan build asset

```bash
npm install
npm run build
```

### 7. Jalankan aplikasi

Untuk pengembangan lokal:

```bash
php artisan serve
```

Buka alamat yang ditampilkan oleh Artisan, umumnya `http://127.0.0.1:8000`. Pastikan Freedom Finger sudah berjalan dan dapat dijangkau jika ingin menguji sinkronisasi mesin.

## Instalasi Server Produksi

Langkah berikut merupakan panduan deployment umum. Sesuaikan konfigurasi dengan kebijakan server yang digunakan.

### 1. Siapkan server

Pasang PHP 8.3 beserta ekstensi yang diperlukan, Composer, MySQL, Node.js/npm, Git, dan Nginx atau Apache.

### 2. Deploy source code

```bash
git clone https://github.com/debaluk/absen.git
cd absen
```

Untuk deployment berikutnya, ambil perubahan dari branch produksi yang telah ditetapkan:

```bash
git pull --ff-only origin main
```

### 3. Konfigurasi environment produksi

Buat `.env` berdasarkan `.env.example`, lalu atur:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` sesuai domain dengan HTTPS
- kredensial database produksi
- `ADMIN_PASSWORD` yang kuat
- URL dan token Freedom Finger

Jangan menyimpan rahasia atau kredensial di Git.

### 4. Install dependency dan build asset

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan key:generate
```

Jika `APP_KEY` sudah digunakan pada instalasi yang berjalan, **jangan membuat ulang key**. Pertahankan key lama agar data terenkripsi dan sesi tidak terganggu.

### 5. Jalankan migration

```bash
php artisan migrate --force
```

Jalankan seeder awal hanya pada instalasi baru dan setelah nilai `ADMIN_PASSWORD` diatur:

```bash
php artisan db:seed --force
```

Jangan menjalankan seeder awal berulang kali tanpa memahami dampaknya. Lakukan backup database sebelum migration pada server produksi.

### 6. Atur document root dan permission

- Arahkan document root virtual host ke direktori `public`, bukan root repository.
- Aktifkan HTTPS.
- Pastikan user proses web dapat menulis ke `storage` dan `bootstrap/cache`.
- Jangan memberi permission tulis ke seluruh source code jika tidak diperlukan.

Contoh permission (sesuaikan user dan group web server):

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

### 7. Bersihkan dan cache konfigurasi

Setelah konfigurasi produksi final:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika deployment berikutnya mengubah konfigurasi atau route, jalankan ulang proses cache yang sesuai.

## Konfigurasi Freedom Finger

Aplikasi membaca konfigurasi integrasi dari:

- `FREEDOM_FINGER_URL`: URL dasar Freedom Finger yang dapat diakses dari server Laravel.
- `FREEDOM_FINGER_TOKEN`: token autentikasi API Freedom Finger.

Contoh:

```dotenv
FREEDOM_FINGER_URL=http://127.0.0.1:8013
FREEDOM_FINGER_TOKEN=ISI_TOKEN_ENGINE
```

URL `127.0.0.1` hanya benar jika Freedom Finger berjalan pada host/network namespace yang sama dengan aplikasi Laravel. Jika engine berada di server lain atau container berbeda, gunakan alamat host dan port yang dapat dijangkau dari server Laravel.

Pastikan firewall hanya membuka port yang memang diperlukan dan batasi akses API engine sesuai konfigurasi keamanan jaringan. Jangan menebak port atau alamat mesin; gunakan konfigurasi aktual Freedom Finger dan perangkat.

## Pemeriksaan Setelah Instalasi

- [ ] Halaman aplikasi dapat dibuka melalui domain atau alamat lokal.
- [ ] Halaman administrasi `/admin` dapat diakses.
- [ ] Akun administrator dapat digunakan untuk login.
- [ ] Koneksi database berhasil.
- [ ] Migration selesai tanpa error.
- [ ] Data perusahaan dan lokasi kerja dapat dikelola.
- [ ] Data karyawan dapat dikelola.
- [ ] Perangkat terdaftar dengan `cloud_id` yang benar.
- [ ] Server Laravel dapat menjangkau Freedom Finger.
- [ ] Sinkronisasi log berhasil dan data tersimpan pada tabel `logs`.

## Pemeliharaan dan Troubleshooting

### Periksa konfigurasi Laravel

```bash
php artisan about
php artisan config:clear
php artisan optimize:clear
```

### Periksa migration

```bash
php artisan migrate:status
```

### Periksa log aplikasi

```bash
tail -f storage/logs/laravel.log
```

### Masalah koneksi Freedom Finger

1. Pastikan service Freedom Finger aktif.
2. Pastikan `FREEDOM_FINGER_URL` benar dan dapat dijangkau dari server Laravel.
3. Pastikan `FREEDOM_FINGER_TOKEN` sama dengan token yang dikonfigurasi pada engine.
4. Periksa firewall, port, dan log engine.
5. Ulangi sinkronisasi setelah koneksi dipastikan normal.

### Setelah mengubah file environment

```bash
php artisan config:clear
php artisan optimize:clear
```

## Status Pengembangan

Dokumentasi ini diperbarui mengikuti implementasi repository. Modul rekapitulasi dan proses pembentukan hasil kehadiran harian perlu didokumentasikan lebih lanjut setelah desain data dan implementasinya ditetapkan.

Perubahan aturan bisnis, struktur database, integrasi mesin, dan prosedur instalasi harus disertai pembaruan dokumentasi ini agar README tetap menjadi acuan proyek.
