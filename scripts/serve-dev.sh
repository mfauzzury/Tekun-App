#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../public"
PORT="${SERVER_PORT:-8000}"
HOST="${SERVER_HOST:-127.0.0.1}"
SERVER_ROUTER="../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"

# Run PHP built-in server directly so -d upload limits apply to HTTP requests.
exec php \
  -d upload_max_filesize=12M \
  -d post_max_size=14M \
  -d memory_limit=256M \
  -d max_execution_time=420 \
  -S "${HOST}:${PORT}" \
  "$SERVER_ROUTER"
