#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="$(cd "$(dirname "$(dirname "$0")")" && pwd)"
UNIT_NAME="hadirra-queue-worker"
SERVICE_FILE="/etc/systemd/system/$UNIT_NAME.service"

if [ "$EUID" -ne 0 ]; then
    echo "Jalankan installer dengan sudo: sudo bash deployment/install-queue-worker.sh" >&2
    exit 1
fi

if [ ! -f "$APP_DIR/artisan" ] || [ ! -f "$APP_DIR/.env" ] || [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    echo "Folder aplikasi tidak lengkap: $APP_DIR" >&2
    echo "Pastikan .env dan vendor sudah tersedia sebelum memasang service." >&2
    exit 1
fi

APP_USER="$(stat -c '%U' "$APP_DIR")"
if [ -z "$APP_USER" ] || [ "$APP_USER" = "root" ] || ! id "$APP_USER" >/dev/null 2>&1; then
    echo "Pemilik folder aplikasi harus user Linux non-root yang valid." >&2
    exit 1
fi

APP_GROUP="$(id -gn "$APP_USER")"
PHP_BIN="$(command -v php || true)"
if [ -z "$PHP_BIN" ]; then
    echo "PHP CLI tidak ditemukan pada PATH." >&2
    exit 1
fi

if ! command -v systemctl >/dev/null 2>&1; then
    echo "systemd tidak tersedia pada server ini." >&2
    exit 1
fi

cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=HADIRRA Laravel Queue Worker
Wants=network-online.target
After=network-online.target

[Service]
Type=simple
User=$APP_USER
Group=$APP_GROUP
WorkingDirectory=$APP_DIR
ExecStart=$PHP_BIN $APP_DIR/artisan queue:work --sleep=2 --tries=1 --timeout=60 --memory=128 --no-interaction
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=70
UMask=0027
NoNewPrivileges=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
EOF

chmod 0644 "$SERVICE_FILE"
systemctl daemon-reload
systemctl enable --now "$UNIT_NAME.service"

echo
echo "Laravel Queue Worker HADIRRA berhasil dipasang."
echo "Aplikasi : $APP_DIR"
echo "User     : $APP_USER"
echo "PHP      : $PHP_BIN"
echo
systemctl --no-pager --full status "$UNIT_NAME.service" || true
