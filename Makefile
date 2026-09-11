# Barangay Profiling System — developer entrypoints.
# Every recipe drives the stack through the Compose v2 CLI (`docker compose`).

SHELL := /bin/bash

DC       := docker compose
DC_DEV   := docker compose --profile dev
EXEC_APP := $(DC) exec -u app app
# One-off tooling: no dependent services, entrypoint bypassed so it does not
# wait on the database for a job that never touches it.
RUN_TOOL := $(DC) run --rm --no-deps --entrypoint sh app -c
RUN_NODE := $(DC) run --rm --no-deps node

# Test-environment overrides, passed as REAL process environment variables.
#
# phpunit.xml declares the same values with force="true", but PHPUnit's force
# only rewrites putenv() and $_ENV — and Illuminate\Support\Env reads $_SERVER
# first, which still holds whatever compose exported into the container. Without
# these flags `php artisan test` would run RefreshDatabase against the
# development database and leave APP_ENV=local (so CSRF made every POST a 419).
TEST_ENV := -e APP_ENV=testing -e DB_DATABASE=brgy_testing -e CACHE_STORE=array \
            -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array \
            -e BROADCAST_CONNECTION=null -e BCRYPT_ROUNDS=4

.DEFAULT_GOAL := help

.PHONY: help build up down restart logs shell psql install setup migrate fresh seed test lint analyse assets clean

help: ## Show this help
	@awk 'BEGIN { FS = ":.*##"; printf "\nBarangay Profiling System\n\nUsage:\n  make \033[36m<target>\033[0m\n\nTargets:\n" } \
		/^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2 } \
		END { printf "\n" }' $(MAKEFILE_LIST)

build: ## Build every image (php, assets) from scratch-ish
	$(DC_DEV) build

up: ## Start the full stack including the dev profile (vite, mailpit)
	$(DC_DEV) up -d --wait

down: ## Stop and remove the containers (volumes are kept)
	$(DC_DEV) down --remove-orphans

restart: ## Restart every running service
	$(DC_DEV) restart

logs: ## Follow the logs of every service
	$(DC_DEV) logs -f --tail=100

shell: ## Open a bash shell in the app container as the `app` user
	$(EXEC_APP) bash

psql: ## Open a psql client inside the database container
	$(DC) exec postgres sh -c 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"'

install: ## Install PHP and JS dependencies
	$(RUN_TOOL) 'composer install'
	$(RUN_NODE) npm install

setup: ## First run: build, boot, key, migrate --seed, build assets
	@test -f .env || cp .env.example .env
	$(MAKE) build
	$(MAKE) install
	$(MAKE) up
	$(EXEC_APP) php artisan key:generate --force
	$(EXEC_APP) php artisan migrate --seed --force
	$(MAKE) assets
	@echo ""
	@echo "  App      -> http://localhost:$${APP_PORT:-8000}"
	@echo "  Mailpit  -> http://localhost:$${MAILPIT_PORT:-8025}"
	@echo ""

migrate: ## Run pending migrations
	$(EXEC_APP) php artisan migrate

fresh: ## Drop everything, re-migrate and re-seed
	$(EXEC_APP) php artisan migrate:fresh --seed

seed: ## Run the database seeders
	$(EXEC_APP) php artisan db:seed

test: ## Run the Pest test suite against the brgy_testing database
	$(DC) exec -u app $(TEST_ENV) app php artisan test

lint: ## Fix code style with Pint
	$(RUN_TOOL) './vendor/bin/pint'

analyse: ## Run PHPStan/Larastan static analysis
	$(RUN_TOOL) './vendor/bin/phpstan analyse --memory-limit=512M'

assets: ## Build the production frontend bundle
	$(RUN_NODE) npm run build

clean: ## Remove containers, volumes and local build artefacts
	$(DC_DEV) down -v --remove-orphans
	rm -rf vendor node_modules public/build public/hot
	rm -rf bootstrap/cache/*.php
	rm -rf storage/framework/cache/data/* storage/framework/views/* storage/logs/*.log
