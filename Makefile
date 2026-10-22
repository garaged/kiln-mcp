.DEFAULT_GOAL := help

.PHONY: help install test lint analyse specs architecture check ci manual-m0

help:
	@printf '%s\n' "Targets: install test lint analyse specs architecture check ci manual-m0"

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
