.DEFAULT_GOAL := help

.PHONY: help
help:
	@perl -nle'print $& if m{^[a-zA-Z_-]+:.*?## .*$$}' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

laravel/storage/docs/api.html: laravel/storage/docs/api.raml
	raml2html laravel/storage/docs/api.raml > laravel/storage/docs/api.html

.PHONY: raml
raml: laravel/storage/docs/api.html ## Render and open api docs with in html.
	open laravel/storage/docs/api.html

.PHONY: build
build: ## Build docker containers.
	docker-compose build

.PHONY: pull
pull: ## Run docker containers.
	docker-compose pull

.PHONY: up
up: ## Run docker containers.
	docker-compose up -d

.PHONY: stop
stop: ## Stop running docker containers.
	docker-compose stop

.PHONY: down
down: stop ## Stop and remove running docker containers.
	docker-compose down
