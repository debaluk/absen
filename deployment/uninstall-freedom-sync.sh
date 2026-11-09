#!/usr/bin/env bash
set -Eeuo pipefail

SCHEDULER_UNIT="hadirra-scheduler"
QUEUE_UNIT="hadirra-queue-worker"

if [[ "${EUID}" -ne 0 ]]; then
    echo "Jalankan dengan sudo: sudo bash deployment/uninstall-freedom-sync.sh" >&2
    exit 1
fi

systemctl disable --now "${SCHEDULER_UNIT}.timer" 2>/dev/null || true
systemctl disable --now "${QUEUE_UNIT}.service" 2>/dev/null || true
rm -f "/etc/systemd/system/${SCHEDULER_UNIT}.timer" "/etc/systemd/system/${SCHEDULER_UNIT}.service" "/etc/systemd/system/${QUEUE_UNIT}.service"
systemctl daemon-reload
systemctl reset-failed "${SCHEDULER_UNIT}.service" "${QUEUE_UNIT}.service" 2>/dev/null || true

echo "Service scheduler dan queue worker HADIRRA berhasil dilepas."
