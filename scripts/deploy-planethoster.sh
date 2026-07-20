#!/usr/bin/env bash
set -euo pipefail

require_env() {
    local name="$1"
    if [ -z "${!name:-}" ]; then
        echo "Variable requise manquante: ${name}" >&2
        exit 1
    fi
}

require_env CRM_DEPLOY_HOST
require_env CRM_DEPLOY_USER
require_env CRM_DEPLOY_PATH

ROOT_DIR="$(git rev-parse --show-toplevel)"
cd "$ROOT_DIR"
export COPYFILE_DISABLE=1

if [ "${CRM_DEPLOY_ALLOW_DIRTY:-0}" != "1" ] && [ -n "$(git status --porcelain)" ]; then
    echo "Le depot contient des changements non commites. Utilisez CRM_DEPLOY_ALLOW_DIRTY=1 pour forcer." >&2
    exit 1
fi

CRM_DEPLOY_PORT="${CRM_DEPLOY_PORT:-22}"
CRM_DEPLOY_TMP_DIR="${CRM_DEPLOY_TMP_DIR:-/home/${CRM_DEPLOY_USER}}"
CRM_DEPLOY_COMPOSER="${CRM_DEPLOY_COMPOSER:-composer}"
CRM_DEPLOY_BUILD="${CRM_DEPLOY_BUILD:-1}"
REVISION="$(git rev-parse --short HEAD)"
TIMESTAMP="$(date +%Y%m%d%H%M%S)"
ARCHIVE_NAME="crm-release-${REVISION}-${TIMESTAMP}.tgz"
LOCAL_ARCHIVE="$(mktemp -t "${ARCHIVE_NAME}.XXXXXX")"
LOCAL_ARCHIVE_TAR="$(mktemp -t "${ARCHIVE_NAME}.tar.XXXXXX")"
REMOTE_ARCHIVE="${CRM_DEPLOY_TMP_DIR}/${ARCHIVE_NAME}"

cleanup() {
    rm -f "$LOCAL_ARCHIVE" "$LOCAL_ARCHIVE_TAR"
}
trap cleanup EXIT

if [ "$CRM_DEPLOY_BUILD" != "0" ]; then
    npm run build
fi

ssh -p "$CRM_DEPLOY_PORT" "${CRM_DEPLOY_USER}@${CRM_DEPLOY_HOST}" "mkdir -p '${CRM_DEPLOY_TMP_DIR}'"

if [ "${CRM_DEPLOY_ALLOW_DIRTY:-0}" = "1" ]; then
    tar -czf "$LOCAL_ARCHIVE" \
        --exclude='.git' \
        --exclude='.env' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='storage/app/private' \
        --exclude='storage/app/public' \
        --exclude='storage/framework/cache' \
        --exclude='storage/framework/sessions' \
        --exclude='storage/framework/testing' \
        --exclude='storage/framework/views' \
        --exclude='storage/logs' \
        --exclude='storage/pail' \
        --exclude='storage/redis' \
        --exclude='test-results' \
        --exclude='playwright-report' \
        .
else
    git archive --format=tar --output="$LOCAL_ARCHIVE_TAR" HEAD

    if [ "$CRM_DEPLOY_BUILD" != "0" ] && [ -d public/build ]; then
        tar -rf "$LOCAL_ARCHIVE_TAR" public/build
    fi

    gzip -c "$LOCAL_ARCHIVE_TAR" > "$LOCAL_ARCHIVE"
fi

scp -P "$CRM_DEPLOY_PORT" "$LOCAL_ARCHIVE" "${CRM_DEPLOY_USER}@${CRM_DEPLOY_HOST}:${REMOTE_ARCHIVE}"

ssh -p "$CRM_DEPLOY_PORT" "${CRM_DEPLOY_USER}@${CRM_DEPLOY_HOST}" \
    "CRM_DEPLOY_PATH='${CRM_DEPLOY_PATH}' REMOTE_ARCHIVE='${REMOTE_ARCHIVE}' REVISION='${REVISION}' CRM_DEPLOY_COMPOSER='${CRM_DEPLOY_COMPOSER}' bash -s" <<'REMOTE'
set -euo pipefail
export COPYFILE_DISABLE=1

cd "$CRM_DEPLOY_PATH"
BACKUP_DIR="$(dirname "$CRM_DEPLOY_PATH")/crm_deploy_backups"
mkdir -p "$BACKUP_DIR"
BACKUP_PATH="${BACKUP_DIR}/crm-before-${REVISION}-$(date +%Y%m%d%H%M%S).tgz"

tar -czf "$BACKUP_PATH" \
    --exclude='storage/redis/*' \
    --exclude='storage/logs/*' \
    --exclude='vendor' \
    --exclude='node_modules' \
    .

tar -xzf "$REMOTE_ARCHIVE" -C "$CRM_DEPLOY_PATH"
printf '%s\n' "$REVISION" > .deployed-revision

if [ -f .env ]; then
    if grep -q '^CRM_ASSET_VERSION=' .env; then
        sed -i.bak "s/^CRM_ASSET_VERSION=.*/CRM_ASSET_VERSION=${REVISION}/" .env
        rm -f .env.bak
    else
        printf '\nCRM_ASSET_VERSION=%s\n' "$REVISION" >> .env
    fi
fi

"$CRM_DEPLOY_COMPOSER" install --no-dev --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan migrate --force
php artisan crm:publish-module-assets --force
php artisan optimize
php artisan view:cache
rm -f "$REMOTE_ARCHIVE"

echo "Deploiement termine: ${REVISION}"
echo "Backup: ${BACKUP_PATH}"
REMOTE
