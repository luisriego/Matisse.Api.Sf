#!/bin/bash

UID = $(shell id -u)
DOCKER_BE = matisse-app

help: ## Show this help message
	@echo 'usage: make [target]'
	@echo
	@echo 'targets:'
	@egrep '^(.+)\:\ ##\ (.+)' ${MAKEFILE_LIST} | column -t -c 2 -s ':#'

start: ## Start the containers
	docker network create matisse-network || true
	cp -n docker-compose.yml.dist docker-compose.yml || true
	U_ID=${UID} docker-compose up -d

start-ai: ## Start containers including Ollama (BankStatement / embeddings)
	docker network create matisse-network || true
	cp -n docker-compose.yml.dist docker-compose.yml || true
	U_ID=${UID} docker-compose --profile ai up -d

stop: ## Stop the containers
	U_ID=${UID} docker-compose stop

restart: ## Restart the containers
	$(MAKE) stop && $(MAKE) start

build: ## Rebuilds all the containers and installs PHP dependencies
	docker network create matisse-network || true
	cp -n docker-compose.yml.dist docker-compose.yml || true
	U_ID=${UID} docker-compose build
	U_ID=${UID} docker-compose pull matisse-db
	$(MAKE) start
	$(MAKE) db-init-extensions

prepare: ## Runs backend commands
	$(MAKE) composer-install

run: ## starts the Symfony development server in detached mode
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} symfony serve -d

logs: ## Show Symfony logs in real time
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} symfony server:log

# Backend commands
composer-install: ## Installs composer dependencies
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} composer install --no-interaction
# End backend commands

analyze: ## Run PHP-CS-Fixer dry-run (src + tests)
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} composer analyze:standards

fix: ## Apply PHP-CS-Fixer (src + tests)
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} composer fix:standards

phpstan: ## Run PHPStan static analysis
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} composer analyze:phpstan

.PHONY: ci
ci: ## Mirror GitHub Actions CI (standards + phpstan + tests)
	$(MAKE) analyze
	$(MAKE) phpstan
	$(MAKE) tests

ssh: ## bash into the be container
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} bash

.PHONY: db-init-extensions
db-init-extensions: ## Enable pgvector on existing databases (after DB container is up)
	@echo "Waiting for PostgreSQL..."
	@until U_ID=${UID} docker exec matisse-db pg_isready -U app -q; do sleep 1; done
	U_ID=${UID} docker exec matisse-db psql -U app -d app -c "CREATE EXTENSION IF NOT EXISTS vector;"
	@U_ID=${UID} docker exec matisse-db psql -U app -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='app_test'" | grep -q 1 && \
		U_ID=${UID} docker exec matisse-db psql -U app -d app_test -c "CREATE EXTENSION IF NOT EXISTS vector;" || true

.PHONY: db-test-setup
db-test-setup: ## Prepare app_test schema (aligned with CI: create DB + pgvector + schema sync)
	$(MAKE) db-init-extensions
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console doctrine:database:create --if-not-exists --env=test
	U_ID=${UID} docker exec matisse-db psql -U app -d app_test -c "CREATE EXTENSION IF NOT EXISTS vector;"
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console doctrine:schema:update --force --env=test

.PHONY: db-sync
db-sync: ## Sync dev database schema with Doctrine entities (run after entity mapping changes)
	$(MAKE) db-init-extensions
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console doctrine:schema:update --force

.PHONY: db-migrate
db-migrate: ## Apply pending Doctrine migrations on dev database (production/staging path)
	$(MAKE) db-init-extensions
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: tests
tests: db-test-setup ## Run PHPUnit (syncs app_test schema first)
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} vendor/bin/phpunit -c phpunit.xml.dist

.PHONY: dev-reset-all
dev-reset-all: ## DEV ONLY: wipe all DB data (keeps migrations table)
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console app:dev:reset-data --scope=all --force

.PHONY: dev-reset-movements
dev-reset-movements: ## DEV ONLY: keep users/accounts/types, wipe expenses/incomes/slips/imports
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console app:dev:reset-data --scope=movements --force

.PHONY: server-up
server-up: ## Start play/staging stack (docker-compose.server.yml)
	docker compose --env-file .env.local -f docker-compose.server.yml up -d --build

.PHONY: server-bootstrap
server-bootstrap: ## First-time server setup (migrations + seeds); requires .env.local
	chmod +x scripts/server-bootstrap.sh
	./scripts/server-bootstrap.sh

.PHONY: server-deploy
server-deploy: ## Incremental deploy (pull + compose + migrate); run on server or via GHA
	chmod +x scripts/server-deploy.sh
	./scripts/server-deploy.sh
