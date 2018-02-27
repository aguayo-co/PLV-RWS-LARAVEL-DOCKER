.DEFAULT_GOAL := help

.PHONY: help
help:
	@perl -nle'print $& if m{^[a-zA-Z_-]+:.*?## .*$$}' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

docs/api.raml:

docs/api.html: docs/api.raml
	raml2html docs/api.raml > docs/api.html

.PHONY: raml
raml: docs/api.html ## Render and open api docs with in html.
	open docs/api.html

.PHONY: build
build: pull ## Pull and build docker containers.
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
