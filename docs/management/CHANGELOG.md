# CHANGELOG — Fingerspot Management

Catatan singkat mengenai pekerjaan dan hasil penting proyek. Satu rangkaian pekerjaan dicatat dalam satu log; perbaikan kecil yang masih bagian dari pekerjaan yang sama tidak perlu dibuatkan entri tersendiri.

## LOG-001 — Tata Kelola Dokumentasi
**Tanggal:** 2026-11-08

### Resume Pekerjaan
- Menetapkan dokumen Master Plan sebagai acuan utama rancangan dan keputusan proyek.
- Menetapkan Change Log sebagai ringkasan riwayat pekerjaan dan keputusan penting.

### Hasil
Dokumentasi proyek memiliki acuan utama dan riwayat perubahan yang dapat ditelusuri.

## LOG-002 — Pengembangan Departemen dan Rekap Karyawan
**Tanggal:** 2026-11-09

### Resume Pekerjaan
- Menambahkan pengelolaan Departemen dan pemetaan Jabatan. Satu Jabatan hanya dapat terhubung ke satu Departemen.
- Menambahkan kolom Ikut Rekap (Ya/Tidak) pada daftar Karyawan, dengan nilai bawaan Ya. Pengaturan dilakukan dari daftar, bukan dari form tambah/edit.
- Menyesuaikan Rekap Absensi agar hanya memasukkan karyawan berstatus Aktif dan Ikut Rekap = Ya.

### Hasil
Pengelompokan organisasi dan pengaturan keikutsertaan karyawan dalam Rekap Absensi telah tersedia pada kode aplikasi. Migration dan verifikasi langsung pada aplikasi masih perlu dijalankan.

---

## Pedoman Penulisan
- Satu rangkaian pekerjaan atau satu tujuan perubahan = satu entri log.
- Tulis ringkasan pekerjaan dan hasilnya dengan bahasa sederhana.
- Gabungkan revisi dan perbaikan kecil yang masih satu rangkaian pekerjaan.
- Catat keputusan desain dan aturan bisnis di Master Plan; Change Log cukup merangkum pekerjaan.
- Hasil pengujian hanya dicatat jika benar-benar sudah dijalankan.
- Riwayat commit Git menjadi rincian teknis; Change Log tidak perlu menyalin daftar file atau setiap perubahan kode.
