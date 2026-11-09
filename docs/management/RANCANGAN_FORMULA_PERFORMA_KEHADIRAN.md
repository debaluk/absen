# Rancangan Formula Performa Kehadiran

> **Proyek:** Fingerspot Management  
> **Status:** Rancangan untuk pembahasan — belum menjadi spesifikasi implementasi final  
> **Versi:** 1.0  
> **Tanggal:** 2026-11-10

## 1. Tujuan dan ruang lingkup

Dokumen ini menjadi rancangan awal menu **Formula Performa Kehadiran** dan **Laporan Performa Kehadiran** pada Fingerspot Management. Penilaian dibatasi pada disiplin dan kepatuhan terhadap kewajiban kehadiran sesuai jadwal kerja, bukan penilaian kinerja pekerjaan secara keseluruhan.

Dokumen ini belum menyatakan bahwa fitur sudah diimplementasikan. Bobot, parameter penalti, kategori nilai, dan sejumlah aturan kasus khusus masih perlu disahkan sebelum desain teknis, database, dan kode dibuat.

### 1.1 Ruang lingkup menu

| Menu | Fungsi |
|---|---|
| Formula Performa Kehadiran | Mengelola identitas formula, masa berlaku, indikator, bobot, toleransi, metode pengurangan nilai, dan kategori hasil. |
| Laporan Performa Kehadiran | Menyajikan hasil bulanan, ringkasan organisasi, rincian karyawan, dan penelusuran ke data absensi per tanggal. |

### 1.2 Prinsip rancangan

- Satu formula perusahaan berlaku untuk seluruh cabang dan departemen.
- Jadwal kerja dan kewajiban absensi tetap dihitung berdasarkan data karyawan yang berlaku pada tanggal terkait.
- Izin dan sakit yang disetujui dikecualikan dari hari efektif; sakit tetap dilaporkan sebagai indikator tersendiri.
- Dinas luar diakui sebagai hadir.
- Keterlambatan dan pulang lebih awal dinilai berdasarkan akumulasi menit; frekuensinya tetap dilaporkan.
- ALPA mengurangi tingkat kehadiran dan tidak diberi penalti tambahan pada rancangan awal untuk menghindari penghitungan ganda.
- Setiap hasil harus dapat ditelusuri ke jadwal, transaksi izin/sakit/dinas, koreksi, dan log absensi sumber.

## 2. Keputusan desain yang sudah disepakati

| Aspek | Keputusan |
|---|---|
| Izin disetujui | Dikeluarkan dari hari wajib kerja / denominator kehadiran. |
| Sakit disetujui | Dikeluarkan dari hari wajib kerja / denominator, tetapi jumlah hari sakit tetap dilaporkan terpisah. |
| Dinas luar | Diakui sebagai hadir penuh. |
| Cakupan formula | Satu formula untuk seluruh perusahaan; tidak ada formula berbeda per cabang/departemen. |
| Keterlambatan | Dinilai berdasarkan total akumulasi menit keterlambatan. |
| Pulang lebih awal | Dinilai berdasarkan total akumulasi menit pulang lebih awal. |
| Periode penilaian | Bulanan. |

Perubahan kebijakan di kemudian hari harus dilakukan melalui revisi keputusan dan versi formula, bukan perubahan diam-diam pada hasil periode yang sudah ditutup.

## 3. Definisi indikator dan status

| Indikator/status | Definisi operasional | Pengaruh pada perhitungan |
|---|---|---|
| Hari wajib kerja | Tanggal yang menurut jadwal kerja karyawan merupakan hari kerja dan berada dalam masa kewajiban absensi. | Dasar awal hari efektif. |
| Hadir fisik | Karyawan memiliki catatan masuk/keluar valid sesuai aturan pencocokan mesin dan koreksi yang disetujui. | Masuk pembilang kehadiran. |
| Dinas luar | Penugasan dinas yang disetujui dan berlaku pada tanggal terkait. | Diakui hadir penuh; masuk pembilang. |
| Izin disetujui | Izin yang statusnya telah disetujui dan mencakup tanggal terkait. | Mengurangi denominator; dilaporkan tersendiri. |
| Sakit disetujui | Ketidakhadiran sakit yang telah disetujui sesuai alur perusahaan. | Mengurangi denominator; dilaporkan tersendiri. |
| ALPA | Hari wajib kerja tanpa kehadiran yang memenuhi syarat dan tanpa izin/sakit/dinas yang sah. | Tetap dalam denominator dan menurunkan persentase hadir. |
| Terlambat | Selisih check-in aktual dari jam mulai jadwal setelah toleransi. | Akumulasi menit menjadi dasar komponen ketepatan waktu. |
| Pulang lebih awal | Selisih checkout aktual terhadap jam selesai jadwal setelah toleransi. | Akumulasi menit menjadi dasar komponen kepatuhan jam kerja. |
| Tidak Dinilai | Tidak ada hari efektif dalam periode atau data belum cukup untuk menghasilkan nilai yang sah. | Tidak diberi nilai 100; ditandai Tidak Dinilai. |

### 3.1 Hari efektif

**Hari efektif = Hari wajib kerja − Hari izin disetujui − Hari sakit disetujui.**

Perhitungan dilakukan berdasarkan tanggal dan cakupan izin/sakit yang benar-benar disetujui. Hari libur dan hari tanpa jadwal tidak termasuk hari wajib kerja sejak awal.

### 3.2 Kehadiran yang diakui

**Hari hadir yang diakui = Hari hadir fisik + Hari dinas luar yang disetujui.**

Satu tanggal hanya boleh dihitung satu kali sebagai hari hadir, walaupun terdapat lebih dari satu bukti atau status pada tanggal yang sama.

### 3.3 ALPA

ALPA hanya dapat dibentuk pada tanggal yang memenuhi seluruh kondisi berikut:

1. Karyawan sudah memasuki `attendance_start_date` yang berlaku.
2. Penempatan karyawan aktif pada tanggal tersebut.
3. Ada jadwal kerja aktif yang mewajibkan hadir.
4. Tidak ada kehadiran yang memenuhi ketentuan.
5. Tidak ada izin/sakit/dinas yang telah disetujui atau pengecualian yang berlaku.

Tidak ada jadwal aktif berarti tidak otomatis ALPA. Hari libur atau hari tanpa kewajiban kerja juga bukan ALPA. Jika koreksi absensi atau pengajuan izin masih menunggu keputusan, status tanggal perlu ditandai belum final sesuai kebijakan penutupan periode.

## 4. Indikator penilaian dan rancangan bobot

Rancangan awal menggunakan tiga komponen nilai. Bobot berikut merupakan rekomendasi awal dan **belum disahkan**.

| Komponen | Bobot usulan | Makna |
|---|---:|---|
| Tingkat kehadiran | 50% | Mengukur pemenuhan hari wajib kerja setelah pengecualian izin/sakit. |
| Ketepatan waktu | 30% | Mengukur akumulasi menit keterlambatan setelah toleransi. |
| Kepatuhan jam kerja | 20% | Mengukur akumulasi menit pulang lebih awal setelah toleransi. |
| **Total** | **100%** | Bobot harus berjumlah tepat 100%. |

### 4.1 Komponen A — Tingkat kehadiran

**Persentase kehadiran = (Hari hadir fisik + Hari dinas luar) ÷ Hari efektif × 100%.**

Nilai komponen kehadiran (**K**) pada rancangan awal sama dengan persentase kehadiran, dengan batas maksimum 100.

Jika hari efektif = 0, komponen tidak dihitung dan hasil periode ditandai **Tidak Dinilai**. Sistem tidak boleh memberikan nilai 100 secara otomatis.

### 4.2 Komponen B — Ketepatan waktu

Menit terlambat per hari:

**Maksimum(0, waktu check-in aktual − jam mulai jadwal − toleransi keterlambatan).**

Total menit terlambat bulanan adalah penjumlahan menit terlambat harian yang valid.

Nilai ketepatan waktu (**T**) menggunakan parameter pengurangan yang dapat dikonfigurasi:

**T = Maksimum(0, 100 − (total menit terlambat ÷ satuan menit) × poin pengurangan per satuan).**

Contoh simulasi: total terlambat 45 menit; satuan 30 menit; pengurangan 1 poin per satuan.

T = Maksimum(0, 100 − (45 ÷ 30 × 1)) = **98,50**.

### 4.3 Komponen C — Kepatuhan jam kerja

Menit pulang awal per hari:

**Maksimum(0, jam selesai jadwal − waktu checkout aktual − toleransi pulang awal).**

Total menit pulang awal bulanan adalah penjumlahan menit harian yang valid.

Nilai kepatuhan jam kerja (**J**):

**J = Maksimum(0, 100 − (total menit pulang awal ÷ satuan menit) × poin pengurangan per satuan).**

Contoh simulasi: total pulang awal 15 menit; satuan 30 menit; pengurangan 1 poin per satuan.

J = Maksimum(0, 100 − (15 ÷ 30 × 1)) = **99,50**.

### 4.4 Rumus nilai akhir

**Nilai akhir = (K × bobot kehadiran) + (T × bobot ketepatan waktu) + (J × bobot kepatuhan jam kerja).**

Dengan bobot usulan 50% / 30% / 20%:

**Nilai akhir = (K × 0,50) + (T × 0,30) + (J × 0,20).**

Contoh: K = 90,48; T = 98,50; J = 99,50.

Nilai akhir = (90,48 × 0,50) + (98,50 × 0,30) + (99,50 × 0,20) = **94,19**.

### 4.5 Pencegahan penalti ganda

ALPA sudah menurunkan rasio kehadiran karena hari ALPA tetap berada di denominator tetapi tidak masuk pembilang. Karena itu, rancangan awal tidak menambahkan pengurangan nilai ALPA secara terpisah. Jumlah ALPA tetap ditampilkan agar HRD dapat memantau kejadian dan melakukan tindak lanjut.

Dinas luar tidak menghasilkan menit terlambat/pulang awal karena diakui hadir penuh, kecuali perusahaan kelak menetapkan bukti waktu atau aturan khusus untuk dinas. Aturan tersebut belum diputuskan.

## 5. Contoh simulasi satu karyawan

Simulasi menggunakan 24 hari wajib kerja dalam satu bulan: 2 hari izin disetujui, 1 hari sakit disetujui, 1 hari dinas luar, 18 hari hadir fisik, dan 2 hari ALPA.

| Keterangan | Jumlah hari | Perlakuan |
|---|---:|---|
| Hari wajib kerja awal | 24 | Dasar awal. |
| Izin disetujui | 2 | Dikecualikan dari hari efektif. |
| Sakit disetujui | 1 | Dikecualikan dari hari efektif; tetap dilaporkan. |
| **Hari efektif** | **21** | 24 − 2 − 1. |
| Hadir fisik | 18 | Masuk pembilang. |
| Dinas luar | 1 | Masuk pembilang sebagai hadir. |
| **Hari hadir diakui** | **19** | 18 + 1. |
| ALPA | 2 | Tetap dalam denominator; tidak ada penalti terpisah. |

Persentase kehadiran = 19 ÷ 21 × 100% = **90,48%**.

Dengan parameter contoh pada bagian 4, T = 98,50 dan J = 99,50. Nilai akhir ilustratif = **94,19**. Angka ini bukan nilai aktual karyawan.

## 6. Input konfigurasi Formula Performa

### 6.1 Identitas dan masa berlaku

| Field | Tipe/contoh | Aturan |
|---|---|---|
| Kode formula | Otomatis/unik | Tidak boleh duplikat. |
| Nama formula | Teks | Wajib diisi. |
| Deskripsi | Teks panjang | Menjelaskan tujuan dan kebijakan. |
| Tanggal mulai berlaku | Tanggal | Wajib; menentukan periode penerapan. |
| Tanggal akhir berlaku | Tanggal/kosong | Opsional; tidak boleh lebih awal dari tanggal mulai. |
| Status | Draft/Aktif/Nonaktif | Hanya formula aktif dan berlaku yang dipakai untuk periode baru. |
| Cakupan | Perusahaan | Tetap satu formula perusahaan sesuai keputusan. |

### 6.2 Konfigurasi komponen

| Field | Contoh | Validasi |
|---|---|---|
| Aktif | Ya/Tidak | Komponen wajib untuk formula aktif, kecuali kebijakan revisi menyatakan lain. |
| Bobot | 50 / 30 / 20 | Total seluruh bobot = 100%. |
| Metode | Rasio/pengurangan berbasis menit | Harus sesuai jenis indikator. |
| Satuan menit | 30 menit | Harus lebih besar dari 0. |
| Poin pengurangan | 1 poin | Tidak boleh negatif. |
| Nilai minimum | 0 | Batas bawah skor komponen. |
| Nilai maksimum | 100 | Batas atas skor komponen. |

### 6.3 Parameter toleransi dan pengurangan

| Parameter | Contoh simulasi | Keterangan |
|---|---:|---|
| Toleransi keterlambatan | 5 menit | Menit terlambat baru dihitung setelah melewati toleransi. |
| Toleransi pulang awal | 0 menit | Menit pulang awal baru dihitung setelah melewati toleransi. |
| Satuan pengurangan terlambat | 30 menit | Akumulasi menit dibagi satuan ini. |
| Poin pengurangan terlambat | 1 poin | Pengurangan untuk setiap satuan menit. |
| Satuan pengurangan pulang awal | 30 menit | Akumulasi menit dibagi satuan ini. |
| Poin pengurangan pulang awal | 1 poin | Pengurangan untuk setiap satuan menit. |

Nilai contoh bukan standar final. Nilai toleransi dan penalti harus diputuskan sebelum formula diaktifkan.

## 7. Kategori hasil performa

Rentang berikut hanya contoh awal.

| Kategori | Rentang nilai contoh | Makna tindak lanjut |
|---|---:|---|
| Sangat Baik | 98,00–100,00 | Memenuhi standar kehadiran dengan sangat baik. |
| Baik | 95,00–97,99 | Memenuhi standar dengan catatan kecil. |
| Cukup | 90,00–94,99 | Perlu pemantauan pada indikator yang menurunkan nilai. |
| Perlu Evaluasi | Di bawah 90,00 | Perlu penelaahan data dan tindak lanjut HRD. |

Rentang tidak boleh memiliki celah atau tumpang tindih dan harus mencakup nilai 0–100. **Tidak Dinilai** merupakan status khusus, bukan rentang skor.

## 8. Rancangan role dan permission

Role berikut merupakan usulan pembagian hak akses. Implementasi sebaiknya memanfaatkan sistem Role/Permission yang sudah ada; tidak otomatis berarti harus membuat role baru.

| Role | Formula | Laporan |
|---|---|---|
| Administrator Sistem | Kelola penuh, termasuk aktivasi dan penonaktifan sesuai kewenangan. | Lihat seluruh perusahaan. |
| HRD Pusat | Buat/ubah draft, mengelola versi, dan mengaktifkan sesuai otorisasi. | Lihat seluruh perusahaan dan rincian. |
| HRD Cabang | Tidak dapat mengubah formula pusat. | Lihat data cabang sesuai kewenangannya. |
| Kepala Cabang | Tidak dapat mengubah formula. | Lihat data cabang sendiri. |
| Kepala Departemen | Tidak dapat mengubah formula. | Lihat karyawan pada departemennya. |
| Karyawan | Tidak ada akses pengaturan. | Usulan: melihat hasil dan rincian milik sendiri jika kebijakan perusahaan mengizinkan. |

### 8.1 Prinsip pembatasan akses

- Hak akses menu dan cakupan data adalah dua hal berbeda; permission menu tidak otomatis memberi akses seluruh perusahaan.
- Cakupan cabang/departemen harus diterapkan pada query data, bukan hanya menyembunyikan filter di antarmuka.
- Aktivasi formula dan penutupan periode dibatasi kepada role yang ditunjuk.
- Perubahan formula memiliki jejak siapa, kapan, dan apa yang diubah.

## 9. Rancangan laporan

### 9.1 Filter laporan

- Periode bulan dan tahun.
- Cabang.
- Departemen.
- Jabatan.
- Karyawan.
- Kategori performa.
- Status perhitungan: belum dihitung, dihitung, ditinjau, atau ditutup (workflow masih usulan).

### 9.2 Kolom ringkasan

| Kelompok | Isi |
|---|---|
| Identitas | Kode karyawan, nama, jabatan, departemen, cabang. |
| Hari kerja | Hari wajib kerja awal dan hari efektif. |
| Status kehadiran | Hadir fisik, dinas, izin, sakit, ALPA. |
| Disiplin waktu | Total menit terlambat, frekuensi terlambat, total menit pulang awal, frekuensi pulang awal. |
| Penilaian | Nilai tiap komponen, bobot, nilai akhir, kategori. |
| Keterangan | Tidak Dinilai, data belum lengkap, atau catatan koreksi. |

### 9.3 Drill-down per karyawan

Saat pengguna membuka satu karyawan, sistem menampilkan daftar tanggal dalam periode, jadwal yang berlaku, jam masuk/keluar, status hari, sumber data, menit terlambat, menit pulang awal, dan referensi transaksi izin/sakit/dinas/koreksi. Penelusuran bukti sumber mengikuti permission pengguna.

### 9.4 Rekap organisasi

Rekap cabang atau departemen sebaiknya dihitung dari agregasi data dasar (jumlah hari hadir diakui dibagi jumlah hari efektif), bukan rata-rata sederhana persentase individu. Cara ini mencegah karyawan dengan 5 hari efektif memiliki bobot agregat yang sama dengan karyawan yang memiliki 24 hari efektif.

### 9.5 Ekspor

Laporan dapat disiapkan untuk ekspor Excel dengan identitas periode, waktu pembuatan, filter yang digunakan, ringkasan indikator, nilai komponen, nilai akhir, dan rincian. Format ekspor dan kebutuhan kop perusahaan ditentukan pada tahap desain laporan.

## 10. Aturan data, validasi, dan kasus khusus

| Kasus | Aturan rancangan |
|---|---|
| Karyawan mulai absensi di tengah bulan | Perhitungan dimulai dari `attendance_start_date` yang berlaku; hari sebelumnya tidak masuk denominator. |
| Karyawan keluar di tengah bulan | Perhitungan berhenti pada tanggal akhir hubungan/penempatan yang relevan; sumber tanggal akhir perlu diselaraskan dengan model data. |
| Perubahan cabang/penempatan | Gunakan riwayat penempatan yang berlaku pada tanggal terkait; jangan menerapkan cabang terbaru ke seluruh bulan. |
| Perubahan jadwal | Gunakan jadwal yang berlaku pada tanggal masing-masing, bukan satu jadwal global untuk semua tanggal. |
| Tidak ada jadwal | Bukan hari wajib kerja dan tidak otomatis ALPA. |
| Hari libur | Tidak dihitung sebagai hari wajib kerja kecuali ada jadwal kerja khusus yang sah. |
| Izin/sakit parsial | Belum diputuskan: perlu aturan jam/hari parsial dan konversinya terhadap denominator. |
| Dinas beberapa jam | Keputusan saat ini menyatakan dinas diakui hadir; perlakuan dinas parsial masih perlu ditetapkan. |
| Log mesin ganda | Perlu deduplikasi berdasarkan identitas mesin/PIN/waktu dan aturan log valid. |
| Tidak ada checkout | Jangan mengasumsikan pulang tepat waktu; ikuti alur koreksi/pengecualian yang disahkan. |
| Koreksi absensi menunggu persetujuan | Periode belum boleh dianggap final jika koreksi memengaruhi hasil. |
| Periode berjalan | Bedakan hari yang sudah berlalu dari hari mendatang; denominator tidak boleh memasukkan hari kerja masa depan. |
| Hari efektif nol | Status Tidak Dinilai, bukan 100 atau 0. |
| Formula berubah | Simpan versi formula dan kaitkan hasil periode ke versi yang digunakan. |

## 11. Siklus proses yang diusulkan

1. HRD Pusat membuat formula dengan status Draft.
2. HRD mengisi indikator, bobot, toleransi, parameter pengurangan, dan kategori nilai.
3. Sistem memvalidasi bobot 100%, rentang kategori, parameter numerik, dan masa berlaku.
4. Formula ditinjau dan diaktifkan oleh role yang berwenang.
5. Pada akhir periode bulanan, sistem menghitung hasil berdasarkan jadwal, penempatan, `attendance_start_date`, log absensi, dan transaksi yang disetujui.
6. HRD meninjau anomali atau data belum lengkap dan melakukan koreksi melalui alur transaksi sumber, bukan mengedit skor secara langsung.
7. Periode ditutup setelah pemeriksaan; hasil dan versi formula disimpan untuk audit.
8. Laporan dilihat dan diekspor sesuai cakupan permission.

Status workflow Draft, Dihitung, Ditinjau, dan Ditutup masih merupakan usulan. Mekanisme buka ulang periode dan otorisasi koreksi belum diputuskan.

## 12. Risiko dan kontrol

| Risiko | Kontrol yang disarankan |
|---|---|
| Penalti ganda untuk ALPA | Jangan memberi penalti ALPA terpisah selama ALPA sudah menurunkan rasio kehadiran. |
| Perbandingan tidak adil antarjadwal | Gunakan jadwal karyawan per tanggal; agregasi organisasi dari total hari, bukan rata-rata persentase. |
| Data izin/sakit belum disetujui | Hanya transaksi dengan status disetujui yang menjadi pengecualian. |
| Formula lama berubah | Versioning dan snapshot hasil per periode. |
| Log mesin tidak lengkap | Tampilkan status data belum lengkap dan proses koreksi; jangan membuat asumsi diam-diam. |
| Akses lintas cabang | Terapkan pembatasan cakupan data di backend. |
| Periode berjalan bias | Hitung hanya hari yang sudah berlalu dan wajib kerja sampai tanggal cut-off. |

## 13. Keputusan yang masih terbuka

| No. | Topik | Status/pertanyaan |
|---:|---|---|
| 1 | Bobot komponen | Usulan 50% / 30% / 20%; belum disahkan. |
| 2 | Toleransi keterlambatan | Apakah menjadi parameter formula atau mengikuti konfigurasi jadwal kerja? |
| 3 | Toleransi pulang awal | Apakah menjadi parameter formula atau mengikuti konfigurasi jadwal kerja? |
| 4 | Pembulatan | Dua angka desimal atau bilangan bulat? Pada tahap komponen atau hanya nilai akhir? |
| 5 | Satuan penalti menit | Contoh 30 menit per 1 poin; nilai final belum ditentukan. |
| 6 | Kategori hasil | Rentang 98/95/90 hanya contoh; perlu persetujuan. |
| 7 | Izin/sakit parsial | Bagaimana menghitung izin atau sakit sebagian hari/jam? |
| 8 | Dinas parsial | Apakah dinas beberapa jam tetap dihitung hadir penuh? |
| 9 | Tidak ada checkout | Apakah menunggu koreksi, dikecualikan, atau mengikuti aturan khusus? |
| 10 | Penutupan periode | Siapa yang meninjau, menutup, dan dapat membuka ulang periode? |
| 11 | Perubahan formula | Apakah formula baru berlaku mulai bulan berikutnya atau bisa mulai pada tanggal tertentu? |
| 12 | Akses karyawan | Apakah karyawan boleh melihat skor dan detail performanya sendiri? |

## 14. Rekomendasi tahapan berikutnya

1. Sahkan bobot dan cara pengurangan nilai untuk keterlambatan serta pulang awal.
2. Sahkan sumber toleransi: dari formula atau dari jadwal kerja.
3. Sahkan pembulatan dan kategori hasil.
4. Selesaikan aturan izin/sakit parsial, dinas parsial, dan data checkout yang tidak lengkap.
5. Audit struktur data jadwal, penempatan, izin, sakit, dinas, koreksi, dan log mesin yang sudah ada.
6. Setelah aturan dan audit data disetujui, susun ERD/migration dan rancangan antarmuka.
7. Lakukan uji hitung dengan kasus contoh sebelum laporan diaktifkan.

## Lampiran A — Contoh tabel hasil hitung

| Indikator | Nilai contoh | Keterangan |
|---|---:|---|
| Hari wajib kerja | 24 | Sesuai jadwal bulan berjalan. |
| Izin disetujui | 2 hari | Dikecualikan. |
| Sakit disetujui | 1 hari | Dikecualikan, dilaporkan terpisah. |
| Hari efektif | 21 | 24 − 2 − 1. |
| Hadir fisik | 18 hari | Kehadiran mesin/koreksi valid. |
| Dinas luar | 1 hari | Diakui hadir. |
| ALPA | 2 hari | Menurunkan persentase hadir. |
| Persentase hadir (K) | 90,48 | 19 ÷ 21 × 100. |
| Total terlambat | 45 menit | Contoh parameter: 30 menit = 1 poin. |
| Nilai ketepatan waktu (T) | 98,50 | 100 − (45 ÷ 30 × 1). |
| Total pulang awal | 15 menit | Contoh parameter: 30 menit = 1 poin. |
| Nilai kepatuhan jam kerja (J) | 99,50 | 100 − (15 ÷ 30 × 1). |
| Nilai akhir | 94,19 | (90,48 × 50%) + (98,50 × 30%) + (99,50 × 20%). |

## Lampiran B — Batasan dokumen

Dokumen ini adalah rancangan konseptual berdasarkan keputusan yang telah diberikan dalam diskusi dan rekomendasi awal. Dokumen ini tidak menyatakan bahwa struktur database, menu, permission, formula, atau laporan sudah tersedia di aplikasi. Audit kode dan skema database aktual tetap diperlukan sebelum menetapkan rancangan teknis.

Bobot 50/30/20, parameter contoh 30 menit = 1 poin, toleransi 5 menit, dan kategori nilai adalah ilustrasi untuk memudahkan pembahasan. Jangan menggunakannya sebagai kebijakan operasional sebelum disetujui.
