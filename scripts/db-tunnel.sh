#!/usr/bin/env bash
# Opens an SSH tunnel so Laravel can reach the private MySQL host via the bastion.
# Keep this terminal open while developing. Use from Git Bash / WSL / macOS / Linux.

set -euo pipefail

SSH_KEY="/c/Tekun/bastion-key.pem"
BASTION="ec2-user@43.217.107.131"
REMOTE_DB_HOST="ip-10-103-0-101.ap-southeast-5.compute.internal"
LOCAL_PORT=3307
REMOTE_PORT=3306

if [[ ! -f "$SSH_KEY" ]]; then
  echo "SSH key not found: $SSH_KEY" >&2
  exit 1
fi

echo "Starting DB tunnel: localhost:${LOCAL_PORT} -> ${REMOTE_DB_HOST}:${REMOTE_PORT} (via ${BASTION})"
echo "Press Ctrl+C to stop."

ssh -i "$SSH_KEY" -L "${LOCAL_PORT}:${REMOTE_DB_HOST}:${REMOTE_PORT}" "$BASTION" -N
