export COMPOSER_CACHE_DIR_HOST = $(HOME)/.cache/composer
export HOST_USER_UID = $(shell id -u)
export HOST_USER_GID = $(shell id -g)

DC_DEV = docker compose -f docker-compose.prod.yaml -f docker-compose.yaml --profile app

up:
	$(DC_DEV) up -d

down:
	$(DC_DEV) down

build:
	$(DC_DEV) build

cmd:
	$(DC_DEV) exec -it app bash -c "$(c)"

console:
	$(DC_DEV) exec -it app bash -c "php bin/console $(c)"

cc:
	$(DC_DEV) exec -it app bash -c "php bin/console cache:clear"

diff:
	$(DC_DEV) exec -it app bash -c "php bin/console do:mi:di --formatted"

warmup:
	$(DC_DEV) exec -it app bash -c "php bin/console cache:warmup --env=dev"

migrate:
	$(DC_DEV) exec -it app bash -c "php bin/console do:mi:mi"

cs_fix:
	$(DC_DEV) exec -it app bash -c "php vendor/bin/php-cs-fixer fix"

phpstan_analyse:
	$(DC_DEV) exec -it app bash -c "php bin/console cache:warmup --env=dev && php -d memory_limit=512M vendor/bin/phpstan analyse"

composer_install:
	$(DC_DEV) run --rm --no-deps -u $(HOST_USER_UID):$(HOST_USER_GID) app sh -c "composer install"

composer_require:
	$(DC_DEV) run --rm --no-deps -u $(HOST_USER_UID):$(HOST_USER_GID) app sh -c "composer require $(packs)"

composer_remove:
	$(DC_DEV) run --rm --no-deps -u $(HOST_USER_UID):$(HOST_USER_GID) app sh -c "composer remove $(packs)"

bashPhp:
	$(DC_DEV) exec -it app bash

# Пересоздание Kibana Data View app-logs*
kibana_init:
	$(DC_DEV) run --rm kibana-init

test:
	$(DC_DEV) exec app php vendor/bin/phpunit

test_unit:
	$(DC_DEV) exec app php vendor/bin/phpunit --testsuite unit

test_functional:
	$(DC_DEV) exec app php vendor/bin/phpunit --testsuite functional

test_db_create:
	$(DC_DEV) exec app php bin/console doctrine:database:create --env=test --if-not-exists
	$(DC_DEV) exec app php bin/console doctrine:migrations:migrate --env=test -n

test_db_reset:
	$(DC_DEV) exec app php bin/console doctrine:database:drop --env=test --force --if-exists
	$(DC_DEV) exec app php bin/console doctrine:database:create --env=test
	$(DC_DEV) exec app php bin/console doctrine:migrations:migrate --env=test -n

test_for_ci:
	$(DC_DEV) exec app bash -c "mkdir -p var/test-results && php vendor/bin/phpunit --log-junit var/test-results/junit.xml"
