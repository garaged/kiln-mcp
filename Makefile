.DEFAULT_GOAL := help

COMPOSE_PROJECT ?= kiln-mcp-test
COMPOSE_FILE ?= testing/compose.yaml
COMPOSE = docker compose --project-name $(COMPOSE_PROJECT) --file $(COMPOSE_FILE)

.PHONY: help install test lint analyse specs architecture check ci manual-m0 container-preflight container-build container-up container-test container-logs container-down

help:
	@printf '%s\n' "Targets: install test lint analyse specs architecture check ci manual-m0 container-preflight container-build container-up container-test container-logs container-down"

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

check: specs architecture lint analyse test

ci: check

manual-m0:
	@printf '%s\n' "Follow docs/manual-testing.md#milestone-0."

container-preflight:
	./scripts/container-preflight.sh

container-build: container-preflight
	$(COMPOSE) build --pull

container-up: container-preflight
	$(COMPOSE) up --build --wait wordpress

container-test: container-preflight
	$(COMPOSE) up --build --wait wordpress
	$(COMPOSE) run --rm --no-deps test-runner

container-logs:
	$(COMPOSE) logs --no-color

container-down:
	$(COMPOSE) down --volumes --remove-orphans
