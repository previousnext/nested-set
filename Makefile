#!/usr/bin/make -f

init:
	composer install --prefer-dist --no-progress

test:
	bin/phpunit

.PHONY: init test
