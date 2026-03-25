.PHONY: setup setup-force up down restart logs logs-backend build \
        install migrate migrate-landlord seed rollback \
        horizon-pause horizon-resume horizon-status tinker \
        test test-unit test-feature test-cover \
        lint lint-fix analyse \
        health cache-clear tenants-migrate hosts \
        supervisor-status supervisor-logs-horizon supervisor-logs-scheduler

# ── Ambiente ────────────────────────────────────────────────────────────────
setup:        ## Configura o ambiente do zero (primeira vez)
	@bash setup.sh

setup-force:  ## Reconfigura o ambiente sobrescrevendo .env e caches
	@bash setup.sh --force

up:           ## Sobe todos os serviços em background
	docker compose up -d

down:         ## Para todos os serviços
	docker compose down

restart:      ## Reinicia todos os serviços
	docker compose restart

logs:         ## Exibe logs de todos os serviços (follow)
	docker compose logs -f

logs-backend: ## Exibe logs apenas do backend
	docker compose logs -f backend

build:        ## (Re)builda todas as imagens
	docker compose build --no-cache

# ── Backend ─────────────────────────────────────────────────────────────────
install:      ## Instala dependências PHP
	docker compose exec backend composer install

migrate:      ## Roda migrations do banco de negócio
	docker compose exec backend php artisan migrate

migrate-landlord: ## Roda migrations do landlord (tabela tenants)
	docker compose exec backend php artisan migrate --path=database/migrations/landlord

seed:         ## Roda seeders
	docker compose exec backend php artisan db:seed

rollback:     ## Desfaz a última migration
	docker compose exec backend php artisan migrate:rollback

horizon-pause:  ## Pausa o Horizon (via Artisan)
	docker compose exec backend php artisan horizon:pause

horizon-resume: ## Retoma o Horizon
	docker compose exec backend php artisan horizon:resume

horizon-status: ## Exibe o status atual do Horizon
	docker compose exec backend php artisan horizon:status

tinker:         ## Abre o Laravel Tinker
	docker compose exec backend php artisan tinker

# ── Testes ──────────────────────────────────────────────────────────────────
test:         ## Roda todos os testes
	docker compose exec backend php artisan test

test-unit:    ## Roda apenas testes unitários
	docker compose exec backend php artisan test --testsuite=Unit

test-feature: ## Roda apenas testes de feature
	docker compose exec backend php artisan test --testsuite=Feature

test-cover:   ## Roda testes com cobertura de código (HTML)
	docker compose exec backend php artisan test --coverage-html coverage/

# ── Qualidade ───────────────────────────────────────────────────────────────
lint:         ## Roda PHP CS Fixer (dry-run)
	docker compose exec backend ./vendor/bin/pint --test

lint-fix:     ## Aplica correções de estilo
	docker compose exec backend ./vendor/bin/pint

analyse:      ## Roda PHPStan
	docker compose exec backend ./vendor/bin/phpstan analyse

# ── Utilitários ─────────────────────────────────────────────────────────────
health:       ## Verifica o health check da API
	curl -s http://api.localhost.com/health | jq

cache-clear:  ## Limpa todos os caches
	docker compose exec backend php artisan optimize:clear

tenants-migrate: ## Roda migrations para todos os tenants
	docker compose exec backend php artisan tenants:artisan "migrate"

hosts:        ## Adiciona entradas no /etc/hosts (requer sudo)
	echo "127.0.0.1 app.localhost.com" | sudo tee -a /etc/hosts
	echo "127.0.0.1 api.localhost.com" | sudo tee -a /etc/hosts

# ── Supervisor ───────────────────────────────────────────────────────────────
supervisor-status: ## Status dos processos gerenciados pelo Supervisor
	docker compose exec backend supervisorctl status

supervisor-logs-horizon: ## Logs do Horizon via Supervisor
	docker compose exec backend tail -f /var/log/supervisor/horizon.log

supervisor-logs-scheduler: ## Logs do Scheduler via Supervisor
	docker compose exec backend tail -f /var/log/supervisor/scheduler.log
