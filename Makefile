.DEFAULT_GOAL := help

COMPOSE_PROJECT ?= kiln-mcp-test
COMPOSE_FILE ?= testing/compose.yaml
COMPOSE = docker compose --project-name $(COMPOSE_PROJECT) --file $(COMPOSE_FILE)

.PHONY: help install test lint analyse specs architecture check ci manual-m0 release release-check container-preflight container-build container-up container-test container-logs container-down

help:
	@printf '%s\n' "Targets: install test lint analyse specs architecture check ci manual-m0 release release-check container-preflight container-build container-up container-test container-logs container-down"

install:
	composer install --no-interaction --prefer-dist

test:
	composer test

lint:
	composer lint

analyse:
	composer analyse

specs:
	composer spec-check

architecture:
	composer architecture-check

check: specs architecture lint analyse test release-check

ci: check

release:
	sh ./scripts/build-release.sh

release-check:
	@release_tmp=$$(mktemp -d); \
	trap 'rm -rf "$$release_tmp"' EXIT HUP INT TERM; \
	KILN_RELEASE_DIR="$$release_tmp" sh ./scripts/build-release.sh; \
	sh ./scripts/check-release-archive.sh "$$release_tmp/"*.zip

manual-m0:
	@printf '%s\n' "Follow docs/manual-testing.md#milestone-0."

container-preflight:
	sh ./scripts/container-preflight.sh

container-build: container-preflight
	$(COMPOSE) build --pull

container-up: container-preflight
	@if ! $(COMPOSE) up --build --wait wordpress; then \
		$(COMPOSE) logs --no-color || true; \
		exit 1; \
	fi

container-test: container-preflight
	@if ! $(COMPOSE) up --build --wait wordpress; then \
		$(COMPOSE) logs --no-color || true; \
		exit 1; \
	fi
	$(COMPOSE) build test-runner
	$(COMPOSE) run --rm --no-deps test-runner

container-logs:
	$(COMPOSE) logs --no-color || true

container-down:
	$(COMPOSE) down --volumes --remove-orphans || true
