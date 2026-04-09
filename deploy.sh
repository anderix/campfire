#!/bin/bash
#
# Deploy Campfire to a remote server via rsync over SSH.
#
# Usage: ./deploy.sh [hostname] [destination-path]
#
# Arguments override values from deploy.conf. If no arguments are
# provided, deploy.conf must exist. Copy deploy.conf.example to
# deploy.conf and edit it for your environment.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CONF="${SCRIPT_DIR}/deploy.conf"

HOST=""
DEST=""

if [ -f "$CONF" ]; then
    source "$CONF"
fi

HOST="${1:-$HOST}"
DEST="${2:-$DEST}"

if [ -z "$HOST" ] || [ -z "$DEST" ]; then
    echo "Usage: $0 [hostname] [destination-path]"
    echo ""
    echo "Or create deploy.conf with HOST and DEST values."
    echo "See deploy.conf.example for the format."
    exit 1
fi

echo "Deploying Campfire to ${HOST}:${DEST}"

# Create destination directory if it doesn't exist
ssh "$HOST" "mkdir -p '$DEST'"

# Sync files, excluding dev/local artifacts
rsync -avz --delete \
    --exclude 'db/campfire.db' \
    --exclude 'db/campfire.db-wal' \
    --exclude 'db/campfire.db-shm' \
    --exclude '.git' \
    --exclude 'deploy.sh' \
    --exclude 'deploy.conf' \
    --exclude 'deploy.conf.example' \
    "${SCRIPT_DIR}/" "${HOST}:${DEST}/"

echo "Done. Visit your site to complete setup."
