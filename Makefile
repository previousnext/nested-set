#!/usr/bin/make -f

PHPCS_STANDARD="vendor/drupal/coder/coder_sniffer/Drupal"
PHPCS_DIRS=src tests

init:
	composer install --prefer-dist --no-progress

lint-php:
	bin/phpcs --standard=${PHPCS_STANDARD} ${PHPCS_DIRS}

fix-php:
	bin/phpcbf --standard=${PHPCS_STANDARD} ${PHPCS_DIRS}

test:
	bin/phpunit

.PHONY: init test
