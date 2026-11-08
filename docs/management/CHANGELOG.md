# HADIRRA Management — Change Log

Semua perubahan konsep, aturan bisnis, roadmap, dan dokumentasi manajemen dicatat secara kronologis di sini. Jangan menghapus entri lama; jika keputusan berubah, tambahkan entri baru dan tandai keputusan terdahulu sebagai digantikan pada dokumen induk.

## Format pencatatan

Setiap entri memuat:
- **ID:** nomor unik berurutan (LOG-001, LOG-002, ...).
- **Tanggal:** tanggal perubahan disepakati/dilakukan.
- **Versi:** versi dokumen induk.
- **Area:** bagian atau modul terdampak.
- **Jenis:** penetapan, penambahan, revisi, koreksi, atau implementasi.
- **Perubahan:** ringkasan perubahan dan alasan.
- **Status:** usulan, dalam pembahasan, disetujui/final, ditunda, digantikan, atau selesai diverifikasi.
- **Dampak roadmap:** item roadmap yang berubah, jika ada.

---

## LOG-001 — 2026-11-08

- **Versi:** 1.1
- **Area:** Tata kelola dokumen dan roadmap manajemen
- **Jenis:** Penetapan
- **Perubahan:** Dokumen konsep manajemen HADIRRA ditetapkan sebagai dokumen induk (Single Source of Truth) dan roadmap. Ditambahkan kewajiban memperbarui dokumen induk, roadmap, dan Change Log setiap kali perubahan disepakati.
- **Alasan:** Menjaga kesinambungan keputusan, mencegah kehilangan konteks, dan memastikan setiap revisi dapat ditelusuri.
- **Status:** Disetujui
- **Dampak roadmap:** Seluruh pekerjaan dan perubahan manajemen berikutnya mengikuti tata kelola dokumen ini.

## LOG-002 — 2026-11-09

- **Versi:** 1.2
- **Area:** Organisasi — Departemen dan Jabatan
- **Jenis:** Penetapan dan implementasi awal
- **Perubahan:** Menambahkan master Departemen sebagai pengelompokan Jabatan melalui tabel `departments` dan `department_positions`. Satu Jabatan hanya boleh dipetakan ke satu Departemen. Tabel `positions` dan `employees` tidak ditambahkan kolom `department_id`. Tab Departemen menyediakan CRUD dan mapping Jabatan.
- **Alasan:** Memisahkan struktur hierarki Jabatan dari pengelompokan departemen serta menjaga agar data karyawan tetap merujuk pada Jabatan.
- **Status:** Disetujui/final; implementasi awal menunggu verifikasi aplikasi.
- **Dampak roadmap:** Menambahkan RM-009 — Master departemen dan mapping satu jabatan ke satu departemen.

## LOG-003 — 2026-11-09

- **Versi:** 1.2
- **Area:** Organisasi — perpindahan tab Jabatan dan Departemen
- **Jenis:** Koreksi
- **Perubahan:** Menambahkan identitas DOM `wire:key` yang unik pada konten setiap tab Pengaturan Organisasi agar Livewire tidak menggunakan ulang elemen input antar-tab yang memiliki struktur serupa.
- **Alasan:** Mencegah nilai input Nama Jabatan dan Kode/Nama Departemen tertukar atau terbawa ketika pengguna berpindah tab.
- **Status:** Perubahan kode diterapkan; menunggu verifikasi pada aplikasi.
- **Dampak roadmap:** Tidak mengubah ruang lingkup maupun status roadmap.

## LOG-004 — 2026-11-09

- **Versi:** 1.2
- **Area:** Karyawan — keikutsertaan dalam rekap
- **Jenis:** Penetapan dan implementasi
- **Perubahan:** Menambahkan kolom `include_in_reports` dengan nilai awal `true`. Status Ikut Rekap ditampilkan sebagai Ya/Tidak pada daftar Karyawan dan dapat diubah langsung dengan klik; tidak ditambahkan ke form tambah/edit. Halaman Rekap Absensi hanya menampilkan karyawan aktif dengan `include_in_reports = true`.
- **Alasan:** HR dapat mengecualikan atau menyertakan karyawan dalam rekap tanpa membuka form data karyawan.
- **Status:** Kode dan dokumentasi diterapkan; menunggu verifikasi aplikasi dan migration.
- **Dampak roadmap:** Tidak mengubah ruang lingkup maupun status roadmap.

## Ketentuan operasional

1. Untuk setiap perubahan yang dikerjakan melalui alur kerja proyek ini, perbarui dokumen induk dan Change Log dalam commit yang sama.
2. Jika perubahan hanya berupa usulan yang belum disetujui, catat sebagai usulan; jangan mengubah aturan final.
3. Perubahan roadmap harus memperbarui status dan dependensi item terkait.
4. Untuk perubahan kode, catatan log menjelaskan area/fungsi yang berubah dan hasil pengujian yang benar-benar dijalankan.
5. Jangan menyatakan implementasi selesai tanpa verifikasi.
6. Commit Git menjadi audit trail teknis; Change Log menjadi ringkasan perubahan yang mudah dibaca.
