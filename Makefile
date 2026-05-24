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

stop: ## Stop the containers
	U_ID=${UID} docker-compose stop

restart: ## Restart the containers
	$(MAKE) stop && $(MAKE) start

build: ## Rebuilds all the containers
	docker network create matisse-network || true
	cp -n docker-compose.yml.dist docker-compose.yml || true
	U_ID=${UID} docker-compose build

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

ssh: ## bash into the be container
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} bash

.PHONY: db-sync
db-sync: ## Sync dev database schema with Doctrine entities (run after entity mapping changes)
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console doctrine:schema:update --force

# Sincroniza app_test con el mapping Doctrine antes de PHPUnit (misma fuente de verdad que CI con schema:create).
.PHONY: tests
tests:
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console doctrine:schema:update --force --env=test
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} vendor/bin/phpunit -c phpunit.xml.dist

.PHONY: dev-reset-all
dev-reset-all: ## DEV ONLY: wipe all DB data (keeps migrations table)
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console app:dev:reset-data --scope=all --force

.PHONY: dev-reset-movements
dev-reset-movements: ## DEV ONLY: keep users/accounts/types, wipe expenses/incomes/slips/imports
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console app:dev:reset-data --scope=movements --force