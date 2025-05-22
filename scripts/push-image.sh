#!/usr/bin/env bash
set -euo pipefail
IMAGE_NAME="c0305/enkiflow"
IMAGE_TAG="${1:-local}"
docker buildx build --platform linux/amd64,linux/arm64 -t "$IMAGE_NAME:$IMAGE_TAG" --push .

