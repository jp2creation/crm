#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

PHP_BIN="${PHP_BIN:-php}"
REDIS_SERVER_BIN="${REDIS_SERVER_BIN:-redis-server}"
REDIS_CLI_BIN="${REDIS_CLI_BIN:-redis-cli}"
REDIS_HOST_VALUE="${REDIS_HOST:-127.0.0.1}"
REDIS_PORT_VALUE="${REDIS_PORT:-6379}"
REDIS_DIR="$ROOT/storage/redis"
REDIS_CONFIG="$REDIS_DIR/redis.conf"
REDIS_LOG="$ROOT/storage/logs/redis.log"
HORIZON_LOG="$ROOT/storage/logs/horizon.log"

mkdir -p "$REDIS_DIR" "$ROOT/storage/logs"

if [[ ! -f "$REDIS_CONFIG" ]]; then
    cat > "$REDIS_CONFIG" <<CONFIG
bind 127.0.0.1
protected-mode yes
port ${REDIS_PORT_VALUE}
daemonize yes
pidfile ${REDIS_DIR}/redis.pid
logfile ${REDIS_LOG}
dir ${REDIS_DIR}
dbfilename dump.rdb
appendonly yes
appendfilename "appendonly.aof"
databases 16
CONFIG
fi

if ! "$REDIS_CLI_BIN" -h "$REDIS_HOST_VALUE" -p "$REDIS_PORT_VALUE" ping >/dev/null 2>&1; then
    "$REDIS_SERVER_BIN" "$REDIS_CONFIG"
    sleep 1
fi

if [[ "${1:-}" == "--redis-only" ]]; then
    exit 0
fi

if ! "$PHP_BIN" artisan horizon:status --no-ansi 2>/dev/null | grep -q "running"; then
    nohup "$PHP_BIN" artisan horizon >> "$HORIZON_LOG" 2>&1 &
fi
