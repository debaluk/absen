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
- **Area:** Organisasi dan Karyawan — Departemen, Jabatan, serta rekap absensi
- **Jenis:** Penambahan dan revisi
- **Perubahan:** Menambahkan master Departemen beserta mapping Jabatan melalui tabel `departments` dan `department_positions`, dengan ketentuan satu Jabatan hanya boleh berada pada satu Departemen. Merevisi daftar Karyawan dengan kolom Ikut Rekap (Ya/Tidak) yang dapat diubah langsung dari daftar, tanpa menambahkannya ke form tambah/edit. Rekap Absensi hanya memasukkan karyawan berstatus Aktif dan Ikut Rekap = Ya.
- **Alasan:** Melengkapi pengelompokan organisasi dan memberi kendali sederhana untuk menentukan karyawan yang disertakan dalam rekap absensi.
- **Status:** Kode dan dokumentasi diterapkan; migration serta verifikasi aplikasi masih perlu dijalankan.
- **Dampak roadmap:** Menambahkan RM-009 — Master departemen dan mapping satu jabatan ke satu departemen. Revisi keikutsertaan rekap tidak mengubah ruang lingkup roadmap.

## Ketentuan operasional

1. Untuk setiap perubahan yang dikerjakan melalui alur kerja proyek ini, perbarui dokumen induk dan Change Log dalam commit yang sama.
2. Jika perubahan hanya berupa usulan yang belum disetujui, catat sebagai usulan; jangan mengubah aturan final.
3. Perubahan roadmap harus memperbarui status dan dependensi item terkait.
4. Untuk perubahan kode, catatan log menjelaskan area/fungsi yang berubah dan hasil pengujian yang benar-benar dijalankan.
5. Jangan menyatakan implementasi selesai tanpa verifikasi.
6. Commit Git menjadi audit trail teknis; Change Log menjadi ringkasan perubahan yang mudah dibaca.
