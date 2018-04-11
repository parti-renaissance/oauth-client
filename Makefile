PHPUNIT_ARGS?=-vvv

.DEFAULT_GOAL := help

.PHONY: help

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

##
## Project setup
##---------------------------------------------------------------------------

.PHONY: vendor

deps: vendor ## Install all dependencies required for dev

vendor: composer.lock
	composer install --no-suggest

composer.lock: composer.json
	@echo compose.lock is not up to date.

##
## Tests
##---------------------------------------------------------------------------

.PHONY: test tu phpcs phpcsfix

test: vendor tu phpcs ## Run the PHP tests

tu: vendor ## Run the PHP Unit tests
	vendor/bin/phpunit $(PHPUNIT_ARGS)

phpcs: vendor ## Lint PHP Code
	vendor/bin/php-cs-fixer fix --diff --dry-run --no-interaction -v

phpcsfix: vendor ## Lint and fix PHP code to follow the convention
	vendor/bin/php-cs-fixer fix
