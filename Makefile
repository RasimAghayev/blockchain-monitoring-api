.DEFAULT_GOAL := help
.PHONY: help up down restart ps logs build build-test \
	shell shell-root artisan composer migrate migrate-fresh seed \
	cache-clear test lint verify clean

# Docker-first (CLAUDE.md standing decision 2): every target below runs inside
# this repo's own compose stack. None of them invoke php/composer/npm on the
# host.

ENV_FILE := var/.env

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

$(ENV_FILE):
	@mkdir -p var
	@cp .env.example $(ENV_FILE)
	@echo "Created $(ENV_FILE) from .env.example — review it before first run."

up: $(ENV_FILE) ## Build and start the long-running stack (nginx, php, pgsql, redis)
	docker compose up -d --build nginx php pgsql redis

down: ## Stop and remove the stack's containers (volumes are preserved)
	docker compose down

restart: down up ## Restart the long-running stack

ps: ## Show the status of this stack's containers
	docker compose ps

logs: ## Tail logs from the long-running services
	docker compose logs -f nginx php pgsql redis

build: $(ENV_FILE) ## Build every image (runtime + test)
	docker compose build
	docker compose --profile test build

build-test: $(ENV_FILE) ## Build only the images Phase-9/CI verification needs
	docker compose build test composer pgsql nginx redis

shell: $(ENV_FILE) ## Open a shell in the php-fpm (runtime) container
	docker compose run --rm --entrypoint bash php

shell-root: $(ENV_FILE) ## Open a root shell in the php-fpm (runtime) container (e.g. to chown vendor/)
	docker compose run --rm --user root --entrypoint bash php

composer: $(ENV_FILE) ## Run composer, e.g. `make composer ARGS="require foo/bar"`
	docker compose run --rm --entrypoint composer composer $(ARGS)

artisan: $(ENV_FILE) ## Run an artisan command, e.g. `make artisan ARGS="route:list"`
	docker compose run --rm artisan $(ARGS)

migrate: $(ENV_FILE) ## Run pending migrations
	docker compose run --rm artisan migrate --force

migrate-fresh: $(ENV_FILE) ## Drop every table and re-run all migrations (destroys local data)
	docker compose run --rm artisan migrate:fresh --force

seed: $(ENV_FILE) ## Seed the database (DatabaseSeeder)
	docker compose run --rm artisan db:seed --force

cache-clear: $(ENV_FILE) ## Clear config/route/view/application caches (see docs/adr/0002)
	docker compose run --rm artisan config:clear
	docker compose run --rm artisan route:clear
	docker compose run --rm artisan view:clear
	docker compose run --rm artisan cache:clear

test: $(ENV_FILE) ## Run the PHPUnit suite in the test-stage container
	docker compose --profile test run --rm test

lint: $(ENV_FILE) ## Run Pint in check-only mode in the test-stage container
	docker compose --profile test run --rm lint

verify: build-test test lint ## Full Docker-based verification gate (what CI runs)

clean: ## Remove containers, the default network, and built images for this project
	docker compose --profile test down --rmi local
