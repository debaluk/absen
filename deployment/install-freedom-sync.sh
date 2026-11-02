#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
UNIT_NAME="hadirra-scheduler"
SERVICE_FILE="/etc/systemd/system/${UNIT_NAME}.service"
TIMER_FILE="/etc/systemd/system/${UNIT_NAME}.timer"

if [[ "${EUID}" -ne 0 ]]; then
    echo "Jalankan installer dengan sudo: sudo bash deployment/install-freedom-sync.sh" >&2
    exit 1
fi

if [[ ! -f "${APP_DIR}/artisan" || ! -f "${APP_DIR}/.env" || ! -f "${APP_DIR}/vendor/autoload.php" ]]; then
    echo "Folder aplikasi tidak lengkap: ${APP_DIR}" >&2
    echo "Pastikan .env dan vendor sudah tersedia sebelum memasang service." >&2
    exit 1
fi

APP_USER="$(stat -c '%U' "${APP_DIR}")"
if [[ -z "${APP_USER}" || "${APP_USER}" == "root" ]] || ! id "${APP_USER}" >/dev/null 2>&1; then
    echo "Pemilik folder aplikasi harus user Linux non-root yang valid." >&2
    exit 1
fi

APP_GROUP="$(id -gn "${APP_USER}")"
PHP_BIN="$(command -v php || true)"
if [[ -z "${PHP_BIN}" ]]; then
    echo "PHP CLI tidak ditemukan pada PATH." >&2
    exit 1
fi

if ! command -v systemctl >/dev/null 2>&1; then
    echo "systemd tidak tersedia pada server ini." >&2
    exit 1
fi

cat > "${SERVICE_FILE}" <<EOF
[Unit]
Description=HADIRRA Smart Attendance Laravel Scheduler
Wants=network-online.target
After=network-online.target

[Service]
Type=oneshot
User=${APP_USER}
Group=${APP_GROUP}
WorkingDirectory=${APP_DIR}
ExecStart=${PHP_BIN} ${APP_DIR}/artisan schedule:run --no-interaction
UMask=0027
NoNewPrivileges=true
PrivateTmp=true
EOF

cat > "${TIMER_FILE}" <<EOF
[Unit]
Description=Run HADIRRA scheduler every minute

[Timer]
OnCalendar=*-*-* *:*:00
AccuracySec=1s
Persistent=true
Unit=${UNIT_NAME}.service

[Install]
WantedBy=timers.target
EOF

chmod 0644 "${SERVICE_FILE}" "${TIMER_FILE}"
systemctl daemon-reload
systemctl enable --now "${UNIT_NAME}.timer"

echo
echo "Service scheduler HADIRRA berhasil dipasang."
echo "Aplikasi : ${APP_DIR}"
echo "User     : ${APP_USER}"
echo "PHP      : ${PHP_BIN}"
echo
systemctl --no-pager --full status "${UNIT_NAME}.timer" || true
