# HADIRRA — Dokumen Induk & Roadmap Manajemen

> **Status:** Dokumen acuan proyek  
> **Versi:** 1.1  
> **Tanggal penetapan:** 2026-10-25  
> **Ruang lingkup:** Manajemen SDM dan administrasi kehadiran

Dokumen ini adalah **Single Source of Truth** untuk konsep, aturan bisnis, keputusan desain, dan roadmap HADIRRA pada sisi manajemen. Perubahan yang disepakati wajib diperbarui di sini dan dicatat pada [CHANGELOG.md](./CHANGELOG.md).

## 1. Tujuan dan batas ruang lingkup

HADIRRA pada ruang lingkup dokumen ini adalah aplikasi web untuk mengelola data kepegawaian dan administrasi kehadiran secara terpusat. Fokusnya adalah organisasi, karyawan, penempatan, jadwal, cuti/izin, lembur, pengolahan status kehadiran, koreksi, dan laporan.

Dokumen ini **tidak mengatur implementasi teknis engine atau komunikasi perangkat**. Jika ada kebutuhan teknis di luar manajemen, dokumentasikan pada dokumen teknis terpisah agar ruang lingkup tidak bercampur.

## 2. Prinsip dasar

- Satu perusahaan dapat memiliki banyak karyawan dan banyak cabang/lokasi kerja.
- Pengelolaan manajemen dilakukan secara terpusat.
- Hak akses dibatasi menurut peran dan kewenangan.
- Lokasi kerja karyawan adalah data organisasi/penempatan dan tidak disamakan dengan lokasi perangkat.
- Riwayat penempatan, persetujuan, koreksi, dan perubahan data harus dapat ditelusuri.
- Data kehadiran, cuti, dan lembur memiliki alur serta status masing-masing.
- Bukti kehadiran yang sudah tercatat tidak dihapus hanya karena ada koreksi atau persetujuan administratif.

## 3. Struktur data dan karyawan

Struktur konseptual: **Perusahaan → Cabang/Lokasi Kerja → Jabatan → Karyawan → Penempatan Karyawan → Jadwal Kerja**.

Data karyawan/penempatan yang menjadi perhatian:

| Elemen | Makna |
|---|---|
| `position_id` | Jabatan karyawan |
| `work_location_id` | Lokasi kerja karyawan |
| `hire_date` | Tanggal resmi mulai bekerja di perusahaan |
| `start_date` | Tanggal mulai penempatan pada cabang/lokasi kerja |
| `attendance_start_date` | Tanggal mulai kewajiban absensi pada penempatan |
| `end_date` | Tanggal akhir penempatan, jika ada |
| `PIN` dan `cloud_id` | Identitas referensi yang dipilih untuk mengaitkan karyawan dengan catatan kehadiran |

Ketentuan tanggal:
- `attendance_start_date` tidak boleh mendahului `start_date`.
- Jika penempatan memiliki `end_date`, kewajiban absensi tidak boleh melampaui akhir penempatan.
- Tanggal mulai bekerja, tanggal mulai penempatan, dan tanggal mulai kewajiban absensi adalah tiga hal berbeda.

## 4. Modul manajemen

1. Dashboard.
2. Data perusahaan dan cabang/lokasi kerja.
3. Jabatan.
4. Karyawan.
5. Penempatan kerja dan riwayatnya.
6. Jadwal kerja.
7. Kalender dan hari libur.
8. Cuti dan izin.
9. Lembur.
10. Kehadiran dan koreksi.
11. Laporan dan performa kehadiran.
12. Pengguna dan hak akses.

## 5. Alur utama

1. Siapkan data perusahaan, cabang/lokasi kerja, dan jabatan.
2. Buat data karyawan.
3. Tetapkan jabatan, lokasi kerja, dan tanggal efektif penempatan.
4. Tetapkan tanggal mulai kewajiban absensi.
5. Atur jadwal kerja dan kalender hari libur.
6. Kelola pengajuan cuti/izin dan proses lembur.
7. Olah status kehadiran berdasarkan jadwal, kalender, penempatan, dan persetujuan.
8. Periksa anomali dan proses koreksi dengan persetujuan.
9. Sajikan laporan dan performa kehadiran.

## 6. Aturan status kehadiran dan ALPA

ALPA tidak boleh ditetapkan hanya karena tidak ditemukan catatan kehadiran. Penetapan ALPA sekurang-kurangnya mensyaratkan:
- tanggal sudah mencapai `attendance_start_date`;
- penempatan karyawan aktif pada tanggal tersebut;
- ada jadwal kerja aktif yang mewajibkan hadir;
- tidak ada kehadiran yang memenuhi ketentuan; dan
- tidak ada cuti/izin yang telah disetujui atau pengecualian yang berlaku.

Tidak ada jadwal aktif berarti tidak otomatis ALPA. Hari libur atau hari tanpa kewajiban kerja juga bukan ALPA.

## 7. Cuti dan izin

Kategori yang dapat dikelola meliputi cuti tahunan, sakit, melahirkan, alasan penting, di luar tanggungan perusahaan, izin pribadi, dan izin tidak masuk. Hak, durasi, persyaratan, serta pembayaran mengikuti peraturan yang berlaku dan kebijakan perusahaan.

Alur:
1. Karyawan mengajukan jenis, tanggal, alasan, dan dokumen pendukung bila diperlukan.
2. Sistem memeriksa saldo, jadwal, rentang tanggal, dan benturan.
3. Atasan/HR memeriksa permohonan.
4. Permohonan disetujui, ditolak, atau dikembalikan.
5. Jika disetujui, kalender diperbarui dan saldo disesuaikan menurut jenis cuti.
6. Riwayat pengajuan, keputusan, dan perubahan saldo disimpan.

Aturan:
- Saldo hanya berkurang setelah persetujuan dan sesuai kebijakan jenis cuti.
- Pengajuan ditolak atau dibatalkan tidak mengurangi saldo.
- Cuti setengah hari hanya berlaku jika kebijakan mengizinkan.
- Saldo memiliki histori penambahan, pemakaian, penyesuaian, dan pembatalan.
- Cuti/izin tidak menghapus bukti kehadiran yang sudah ada.

## 8. Lembur

Catatan waktu kehadiran bukan persetujuan lembur. Lembur memerlukan dasar perintah dan persetujuan sesuai ketentuan yang berlaku.

Alur konseptual:
1. Atasan/perusahaan membuat perintah lembur.
2. Karyawan memberikan persetujuan tertulis/digital sesuai ketentuan.
3. Karyawan melaksanakan lembur.
4. Atasan memeriksa dan memverifikasi pelaksanaan serta durasi.
5. Hasil terverifikasi menjadi dasar laporan dan administrasi kompensasi.

Status yang direncanakan: Draft, Menunggu Persetujuan, Disetujui, Berlangsung, Menunggu Verifikasi, Terverifikasi, Ditolak, dan Dibatalkan.

Kategori: hari kerja biasa, istirahat mingguan, dan hari libur resmi.

**Belum final:** algoritma penentuan durasi, penggabungan sesi, pembulatan, toleransi, dan penanganan catatan tidak lengkap. Jangan implementasikan perhitungan final sebelum aturan ini disepakati dan diperiksa terhadap regulasi terkini.

## 9. Performa kehadiran

Performa kehadiran berarti disiplin dan kepatuhan terhadap jadwal, bukan penilaian kinerja pekerjaan secara keseluruhan.

Indikator yang dipertimbangkan:
- tingkat kehadiran;
- ketepatan waktu datang;
- frekuensi dan total durasi keterlambatan;
- frekuensi dan durasi pulang lebih awal;
- jumlah ALPA;
- kepatuhan terhadap jadwal; dan
- kelengkapan catatan kehadiran.

Contoh perhitungan: 20 hari hadir dari 22 hari wajib kerja = 90,91%. Angka ini harus dibaca bersama klasifikasi cuti/izin dan ALPA. Definisi pembilang dan penyebut wajib konsisten.

Rentang contoh sementara (belum menjadi standar final): 98–100% sangat baik; 95–97,99% baik; 90–94,99% cukup; di bawah 90% perlu evaluasi. Bobot, pengecualian, periode, dan dampak penilaian masih perlu disepakati.

## 10. Peran dan hak akses

| Peran | Tanggung jawab konseptual |
|---|---|
| Super Admin | Konfigurasi dan seluruh data perusahaan |
| HR/Admin Pusat | Data karyawan, kebijakan, jadwal, dan laporan lintas cabang |
| Admin Cabang | Operasional sesuai cabang yang menjadi kewenangannya |
| Supervisor/Atasan | Pemeriksaan kehadiran, persetujuan cuti/izin, verifikasi lembur |
| Karyawan | Melihat informasi sendiri dan mengajukan proses yang diizinkan |

## 11. Roadmap

Status roadmap berikut adalah urutan rencana, bukan klaim bahwa fitur sudah selesai diimplementasikan.

| ID | Pekerjaan | Prioritas | Status | Dependensi/catatan |
|---|---|---|---|---|
| RM-001 | Fondasi perusahaan, cabang/lokasi kerja, jabatan, dan karyawan | Tinggi | Dalam roadmap | Fondasi modul manajemen |
| RM-002 | Penempatan karyawan dan tanggal efektif kewajiban absensi | Tinggi | Dalam roadmap | Bergantung pada master karyawan |
| RM-003 | Jadwal kerja dan kalender hari libur | Tinggi | Dalam roadmap | Dasar pengolahan kehadiran |
| RM-004 | Aturan status kehadiran dan ALPA | Tinggi | Dalam roadmap | Bergantung pada penempatan dan jadwal |
| RM-005 | Cuti/izin, persetujuan, saldo, dan histori | Menengah | Dalam roadmap | Bergantung pada kalender dan jadwal |
| RM-006 | Aturan lembur dan proses verifikasi | Menengah | Belum difinalkan | Algoritma dan kebijakan perlu diputuskan |
| RM-007 | Formula performa kehadiran dan laporan | Menengah | Belum difinalkan | Bobot dan kategori perlu diputuskan |
| RM-008 | Audit trail dan pengujian lintas modul | Tinggi | Bertahap | Verifikasi wajib sebelum status selesai |

## 12. Keputusan final dan hal terbuka

### Dasar desain yang telah ditetapkan
- Satu perusahaan, banyak karyawan, banyak cabang/lokasi kerja.
- Pengelolaan manajemen terpusat.
- Lokasi kerja karyawan dibedakan dari lokasi perangkat.
- Jabatan dan lokasi kerja menjadi bagian dari pengelolaan karyawan.
- Tanggal mulai bekerja, penempatan, dan kewajiban absensi dibedakan.
- ALPA hanya jika kewajiban absensi aktif, jadwal aktif, tidak ada kehadiran, dan tidak ada cuti/izin yang disetujui.
- Cuti/izin dan koreksi memerlukan alur persetujuan serta histori.

### Masih perlu keputusan
- Saldo, masa berlaku, carry-over, dan prorata cuti.
- Matriks persetujuan cuti/izin.
- Kebijakan dan algoritma lembur.
- Toleransi keterlambatan/pulang awal.
- Formula, bobot, dan kategori performa.
- Periode penguncian dan pembukaan kembali data.

## 13. Tata kelola perubahan

Dokumen ini adalah acuan utama. Setiap perubahan yang disepakati harus:
1. memperbarui bagian dokumen yang terdampak;
2. menambahkan entri ke `CHANGELOG.md` dalam perubahan/commit yang sama;
3. memperbarui roadmap bila tahapan, dependensi, prioritas, atau status berubah;
4. membedakan usulan, pembahasan, keputusan final, dan hasil implementasi;
5. mempertahankan riwayat keputusan lama yang telah digantikan; dan
6. mencatat hasil verifikasi secara terpisah. Fitur tidak boleh diberi status selesai hanya karena dokumentasinya selesai.

**Aturan commit:** perubahan proyek tidak dianggap lengkap sebelum catatan perubahan manusiawi ditambahkan ke `CHANGELOG.md`. Riwayat commit Git tetap menjadi jejak teknis tambahan.

## 14. Catatan kepatuhan

Dokumen ini adalah rancangan sistem, bukan pendapat hukum. Ketentuan cuti, lembur, waktu kerja, dan kompensasi harus diperiksa terhadap peraturan terbaru serta perjanjian kerja/peraturan perusahaan/perjanjian kerja bersama yang berlaku.
