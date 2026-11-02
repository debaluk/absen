#!/usr/bin/env bash
set -Eeuo pipefail

UNIT_NAME="hadirra-scheduler"

if [[ "${EUID}" -ne 0 ]]; then
    echo "Jalankan dengan sudo: sudo bash deployment/uninstall-freedom-sync.sh" >&2
    exit 1
fi

systemctl disable --now "${UNIT_NAME}.timer" 2>/dev/null || true
rm -f "/etc/systemd/system/${UNIT_NAME}.timer" "/etc/systemd/system/${UNIT_NAME}.service"
systemctl daemon-reload
systemctl reset-failed "${UNIT_NAME}.service" 2>/dev/null || true

echo "Service scheduler HADIRRA berhasil dilepas."
