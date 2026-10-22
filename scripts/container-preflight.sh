#!/bin/sh
set -eu

fail() {
  printf '%s\n' "container preflight failed: $*" >&2
  exit 1
}

if [ -n "${DOCKER_HOST:-}" ]; then
  case "$DOCKER_HOST" in
    tcp://*)
      fail "DOCKER_HOST uses TCP. Select the Rancher Desktop Docker context or a local Unix socket; do not enable unauthenticated TCP."
      ;;
  esac
fi

command -v docker >/dev/null 2>&1 || fail "docker CLI is not installed or not on PATH."
docker version --format '{{.Server.Os}}/{{.Server.Arch}}' >/dev/null 2>&1 || fail "the selected Docker context cannot reach a daemon."
docker compose version >/dev/null 2>&1 || fail "Docker Compose v2 is required."

printf '%s\n' "Docker context: $(docker context show)"
printf '%s\n' "Daemon: $(docker version --format '{{.Server.Os}}/{{.Server.Arch}}')"
printf '%s\n' "Compose: $(docker compose version --short)"
