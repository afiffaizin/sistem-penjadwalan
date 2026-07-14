#!/bin/bash
set -e


echo " SiJadwal Python — Entrypoint"


echo "[entrypoint] Python service starting..."
echo "[entrypoint] Endpoints:"
echo "  POST /api/cleansing/master"
echo "  POST /api/generate-jadwal"

exec "$@"
