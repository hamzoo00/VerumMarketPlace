# Verum — developer shortcuts.
# PHP lives only inside Docker, so every PHP command is proxied into the app container.

DC := docker compose
EXEC := $(DC) exec app
RUN := $(DC) run --rm app

.DEFAULT_GOAL := help
.PHONY: help up down restart build rebuild logs shell root-shell \
        install migrate fresh seed test lint types check optimize fresh-install \
        fix-perms assets \
        tinker queue horizon reverb scout-import ps

help: ## Show available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

up: ## Start the whole stack in the background
	$(DC) up -d

down: ## Stop the stack
	$(DC) down

restart: ## Restart the stack
	$(DC) restart

build: ## Build images
	$(DC) build

rebuild: ## Rebuild images from scratch
	$(DC) build --no-cache

ps: ## Show container status
	$(DC) ps

logs: ## Tail logs (make logs s=app)
	$(DC) logs -f $(s)

shell: ## Shell into the app container
	$(EXEC) bash

root-shell: ## Root shell into the app container (for apt/pecl)
	$(DC) exec -u root app bash

install: ## Install PHP + JS dependencies
	$(RUN) composer install
	$(DC) run --rm vite npm install

migrate: ## Run migrations
	$(EXEC) php artisan migrate

fresh: ## Drop everything and re-migrate with seeds
	$(EXEC) php artisan migrate:fresh --seed

seed: ## Run seeders
	$(EXEC) php artisan db:seed

test: ## Run the test suite
	$(EXEC) php artisan test

lint: ## Fix code style with Pint
	$(EXEC) php ./vendor/bin/pint --parallel

types: ## Static analysis with PHPStan
	$(EXEC) php ./vendor/bin/phpstan analyse

check: ## Run the full quality gate (lint + types + tests)
	$(EXEC) php ./vendor/bin/pint --parallel --test
	$(EXEC) php ./vendor/bin/phpstan analyse --no-progress
	$(EXEC) php artisan test

assets: ## Build production frontend assets
	$(DC) exec -u root vite sh -c 'cd /var/www/html && npm run build'
	$(DC) exec -u root vite chown -R 1000:1000 /var/www/html/public/build

fix-perms: ## Restore exec bits lost on the WSL bind mount
	chmod +x vendor/bin/* 2>/dev/null || true
	$(DC) exec -u root vite sh -c 'chmod -R +x /var/www/html/node_modules/.bin/ 2>/dev/null; chown -R 1000:1000 /var/www/html/node_modules' || true
	@echo "Permissions restored."

optimize: ## Cache config, routes, views
	$(EXEC) php artisan optimize

tinker: ## Open Tinker
	$(EXEC) php artisan tinker

scout-import: ## (Re)index searchable models into Meilisearch
	$(EXEC) php artisan scout:import "App\\Models\\Listing"

fresh-install: ## First-time setup: build, start, migrate
	$(DC) build
	$(DC) up -d
	$(RUN) composer install
	$(EXEC) php artisan key:generate
	$(EXEC) php artisan migrate --seed
	$(EXEC) php artisan storage:link
	@echo "Ready: http://localhost:8000  |  Mail: http://localhost:8025"
