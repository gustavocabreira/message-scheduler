.PHONY: up down build bash test pint phpstan fresh logs restart

# Docker targets
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

bash:
	docker compose exec app bash

logs:
	docker compose logs -f

restart:
	docker compose restart app

# Application targets
test:
	docker compose exec app php artisan test --parallel

pint:
	docker compose exec app ./vendor/bin/pint

phpstan:
	docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M

fresh:
	docker compose exec app php artisan migrate:fresh --seed

# Local development (without Docker)
test-local:
	php artisan test --parallel

pint-local:
	./vendor/bin/pint

phpstan-local:
	./vendor/bin/phpstan analyse --memory-limit=512M

fresh-local:
	php artisan migrate:fresh --seed

# Quality checks (run all)
quality:
	docker compose exec app ./vendor/bin/pint --test
	docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M
	docker compose exec app php artisan test --parallel

quality-local:
	./vendor/bin/pint --test
	./vendor/bin/phpstan analyse --memory-limit=512M
	php artisan test --parallel
