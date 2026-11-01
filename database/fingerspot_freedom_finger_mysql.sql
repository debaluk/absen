-- =========================================================
-- FINGERSPOT / FREEDOM FINGER - MYSQL 8.0+
-- Rancangan baru berdasarkan struktur data Freedom Finger
-- + master organisasi + hierarki jabatan + tabel infrastruktur Laravel
--
-- Catatan desain:
-- 1. Database ini terpisah dari database internal aplikasi/mesin Freedom Finger.
-- 2. machine_users adalah data user yang berasal dari mesin absensi.
--    users adalah akun login administrator/operator aplikasi.
-- 3. PIN karyawan menjadi identitas utama employee, mengikuti model Freedom Finger.
-- 4. employees.dept tetap berupa teks seperti data sumber Freedom Finger.
-- 5. positions menggunakan parent_id untuk hierarki jabatan bertingkat.
-- 6. Belum ada FOREIGN KEY; hubungan disiapkan melalui kolom ID dan indeks.
-- 7. JSON digunakan untuk konfigurasi jadwal 7 hari dan payload mesin.
-- 8. Jangan gunakan kredensial admin bawaan yang diketahui umum.
--    Buat akun admin awal melalui Laravel seeder/command dengan password hash.
-- =========================================================

CREATE DATABASE IF NOT EXISTS fingerspot
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fingerspot;

-- =========================================================
-- A. MASTER ORGANISASI
-- =========================================================

CREATE TABLE companies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(150) NOT NULL,
    address TEXT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_companies_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE branches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(150) NOT NULL,
    address TEXT NULL,
    phone VARCHAR(30) NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Makassar',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_branches_company_code (company_id, code),
    KEY idx_branches_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_locations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    branch_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(150) NOT NULL,
    address TEXT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    radius_meter INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_work_locations_branch_code (branch_id, code),
    KEY idx_work_locations_branch (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hierarki jabatan tidak dibatasi jumlah tingkatnya.
-- parent_id NULL = jabatan paling atas; level adalah nomor tingkat yang dikelola aplikasi.
CREATE TABLE positions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id BIGINT UNSIGNED NULL,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    level INT UNSIGNED NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_positions_code (code),
    KEY idx_positions_parent (parent_id),
    KEY idx_positions_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- B. KARYAWAN APLIKASI
-- =========================================================

CREATE TABLE employees (
    pin VARCHAR(32) NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    branch_id BIGINT UNSIGNED NULL,
    work_location_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    dept VARCHAR(100) NULL,
    schedule VARCHAR(50) NULL COMMENT 'ID jadwal alternatif; NULL = jadwal utama',
    recap TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (pin),
    KEY idx_employees_name (name),
    KEY idx_employees_dept (dept),
    KEY idx_employees_company (company_id),
    KEY idx_employees_branch (branch_id),
    KEY idx_employees_work_location (work_location_id),
    KEY idx_employees_position (position_id),
    KEY idx_employees_schedule (schedule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- C. DATA MESIN ABSENSI / KOMUNIKASI
-- =========================================================

CREATE TABLE devices (
    cloud_id VARCHAR(32) NOT NULL,
    name VARCHAR(100) NULL,
    info TEXT NULL COMMENT 'Informasi firmware/perangkat; dapat berisi JSON',
    fk_time CHAR(14) NULL COMMENT 'Format YYYYMMDDHHMMSS',
    last_activity DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (cloud_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE machine_users (
    cloud_id VARCHAR(32) NOT NULL,
    pin VARCHAR(32) NOT NULL,
    name VARCHAR(100) NULL,
    privilege VARCHAR(20) NULL,
    data MEDIUMBLOB NULL COMMENT 'Payload biner mesin: header, JSON, template biometrik',
    updated DATETIME NULL,
    PRIMARY KEY (cloud_id, pin),
    KEY idx_machine_users_name (name),
    KEY idx_machine_users_pin (pin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cloud_id VARCHAR(32) NOT NULL,
    pin VARCHAR(32) NOT NULL,
    scan_date DATETIME NOT NULL,
    verify TINYINT NULL,
    status_scan TINYINT NULL COMMENT 'Nilai sesuai protokol mesin; verifikasi arti 0/1 terhadap perangkat',
    io_mode INT UNSIGNED NULL,
    verify_mode INT UNSIGNED NULL,
    photo VARCHAR(255) NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_device_pin_time (cloud_id, pin, scan_date),
    KEY idx_logs_scan_date (scan_date),
    KEY idx_logs_pin_date (pin, scan_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cmds (
    trans_id VARCHAR(32) NOT NULL,
    cloud_id VARCHAR(32) NULL,
    type VARCHAR(50) NULL,
    cmd_code VARCHAR(50) NULL,
    param BLOB NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    result_code VARCHAR(20) NULL,
    data MEDIUMTEXT NULL COMMENT 'Respons mesin; umumnya JSON',
    created DATETIME NULL,
    sent DATETIME NULL,
    done DATETIME NULL,
    PRIMARY KEY (trans_id),
    KEY idx_cmds_cloud_status (cloud_id, status),
    KEY idx_cmds_created (created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- D. PENGATURAN JADWAL FREEDOM FINGER
-- =========================================================

-- Satu konfigurasi jadwal utama/default aplikasi.
-- days: array 7 elemen Minggu-Sabtu; null=libur, object jam kerja, {"free":true}=fleksibel.
-- overtime dan overtime_max juga array 7 elemen Minggu-Sabtu.
CREATE TABLE work_schedule_settings (
    id TINYINT UNSIGNED NOT NULL DEFAULT 1,
    days JSON NOT NULL,
    tolerance SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    overtime JSON NOT NULL,
    overtime_min SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    overtime_max JSON NOT NULL,
    min_gap SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jadwal alternatif. Aturan tolerance/overtime_min/min_gap mengikuti pengaturan utama.
CREATE TABLE schedules (
    id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) NULL,
    days JSON NOT NULL,
    overtime JSON NOT NULL,
    overtime_max JSON NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data default Freedom Finger: Minggu libur, Senin-Jumat 08:00-17:00, Sabtu libur.
INSERT INTO work_schedule_settings
    (id, days, tolerance, overtime, overtime_min, overtime_max, min_gap)
VALUES
    (1,
     JSON_ARRAY(NULL,
       JSON_OBJECT('start','08:00','end','17:00'),
       JSON_OBJECT('start','08:00','end','17:00'),
       JSON_OBJECT('start','08:00','end','17:00'),
       JSON_OBJECT('start','08:00','end','17:00'),
       JSON_OBJECT('start','08:00','end','17:00'),
       NULL),
     15,
     JSON_ARRAY(TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE),
     60,
     JSON_ARRAY(0, 0, 0, 0, 0, 0, 0),
     60);

-- =========================================================
-- E. LIBUR, IZIN, DAN KOREKSI ABSENSI
-- =========================================================

CREATE TABLE holidays (
    date DATE NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leaves (
    id VARCHAR(50) NOT NULL,
    pin VARCHAR(32) NOT NULL,
    `from` DATE NOT NULL,
    `to` DATE NOT NULL,
    kind VARCHAR(20) NOT NULL COMMENT 'izin, sakit, cuti, dinas',
    note TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_leaves_pin_dates (pin, `from`, `to`),
    KEY idx_leaves_dates (`from`, `to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE corrections (
    id VARCHAR(50) NOT NULL,
    pin VARCHAR(32) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_corrections_pin_date (pin, date),
    KEY idx_corrections_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- F. AKUN LOGIN APLIKASI (LARAVEL AUTH)
-- =========================================================

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
    id VARCHAR(255) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    PRIMARY KEY (id),
    KEY sessions_user_id_index (user_id),
    KEY sessions_last_activity_index (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- G. CACHE DATABASE DRIVER (LARAVEL)
-- =========================================================

CREATE TABLE cache (
    `key` VARCHAR(255) NOT NULL,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache_locks (
    `key` VARCHAR(255) NOT NULL,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- H. QUEUE DATABASE DRIVER (LARAVEL)
-- =========================================================

CREATE TABLE jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY jobs_queue_index (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE job_batches (
    id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids LONGTEXT NOT NULL,
    options MEDIUMTEXT NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL,
    finished_at INT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid VARCHAR(255) NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY failed_jobs_uuid_unique (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- SEED ADMIN
-- Tidak dibuat dengan password default di SQL ini.
-- Buat user admin melalui Laravel seeder/command dan Hash::make(password).
-- =========================================================
