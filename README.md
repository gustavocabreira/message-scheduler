# MessageScheduler

Sistema web multi-tenant de agendamento de mensagens integrado à plataforma [Huggy](https://huggy.io). Permite que usuários de múltiplas empresas realizem disparos imediatos, agendados e recorrentes para contatos via API REST da Huggy, com suporte a templates, personalização por wildcards, controle de cadência e histórico completo.

---

## Índice

- [Visão geral](#visão-geral)
- [Pré-requisitos](#pré-requisitos)
- [Setup rápido](#setup-rápido)
- [Configuração manual](#configuração-manual)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Serviços e URLs](#serviços-e-urls)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Comandos disponíveis](#comandos-disponíveis)
- [Arquitetura](#arquitetura)
- [Testes](#testes)
- [Observabilidade](#observabilidade)

---

## Visão geral

### Funcionalidades

- **Autenticação** via OAuth2 da Huggy com suporte a múltiplos tenants por usuário
- **Multi-tenancy** — cada empresa opera de forma completamente isolada
- **Disparo de mensagens** — texto, imagem, áudio, vídeo e documentos
- **Modos de disparo** — imediato, agendado (data/hora) e recorrente (intervalo personalizado)
- **Templates** — pessoais ou compartilhados por empresa, com suporte a wildcards
- **Wildcards** — personalização dinâmica por contato (`{{nome_contato}}`, campos customizados da Huggy, etc.)
- **Controle de cadência** — intervalo mínimo entre disparos e limite diário por canal
- **Restrição de horário** — janela de disparo configurável por tenant com respeito ao timezone
- **Controle de duplicidade** — detecção por conteúdo + contato dentro de uma janela de tempo
- **Lista de bloqueio (opt-out)** — contatos que não devem receber disparos
- **Histórico e relatórios** — rastreamento completo com status por contato
- **Notificações** — email e in-app ao concluir ou falhar um disparo
- **Auditoria** — log imutável de todas as ações sensíveis

### Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13 + PHP 8.4 |
| Banco de dados | PostgreSQL 16 |
| Cache / Filas | Redis + Laravel Horizon |
| Agendamento | Laravel Scheduler |
| Processos | Supervisor (dentro do container) |
| Multi-tenancy | spatie/laravel-multitenancy v4 |
| Permissões | spatie/laravel-permission v6 |
| Proxy reverso | Nginx |
| Containerização | Docker + Docker Compose |

---

## Pré-requisitos

Antes de começar, certifique-se de ter instalado:

- [Docker](https://docs.docker.com/get-docker/) >= 24
- [Docker Compose](https://docs.docker.com/compose/install/) >= 2.20
- `make`
- `curl` + `jq` (opcional, para o health check via `make health`)

---

## Setup rápido

> Para a **primeira vez** rodando o projeto.

**1. Clone o repositório**

```bash
git clone https://github.com/sua-org/message-scheduler.git
cd message-scheduler
```

**2. Adicione as entradas no `/etc/hosts`**

```bash
make hosts
# equivalente a:
# echo "127.0.0.1 app.localhost.com" | sudo tee -a /etc/hosts
# echo "127.0.0.1 api.localhost.com" | sudo tee -a /etc/hosts
```

**3. Execute o setup**

```bash
make setup
```

O script irá automaticamente:

- Copiar `backend/.env.example` → `backend/.env`
- Subir PostgreSQL e Redis e aguardar estarem prontos
- Subir o container do backend
- Instalar dependências PHP via Composer
- Gerar o `APP_KEY`
- Rodar migrations do landlord (tabela `tenants`)
- Rodar migrations de negócio
- Rodar o seeder de roles e permissões
- Limpar caches e criar o storage link
- Subir todos os serviços (nginx, frontend)
- Verificar o health check da API

**4. Configure as credenciais da Huggy**

Edite `backend/.env` e preencha:

```env
HUGGY_CLIENT_ID=seu_client_id
HUGGY_CLIENT_SECRET=seu_client_secret
HUGGY_REDIRECT_URI=http://api.localhost.com/auth/callback
```

Pronto. Acesse [http://app.localhost.com](http://app.localhost.com).

---

## Configuração manual

Caso prefira executar cada etapa separadamente:

```bash
# 1. Copiar o .env
cp backend/.env.example backend/.env

# 2. Subir infraestrutura
make up

# 3. Instalar dependências PHP
make install

# 4. Gerar APP_KEY
docker compose exec backend php artisan key:generate

# 5. Migrations do landlord
make migrate-landlord

# 6. Migrations de negócio
make migrate

# 7. Seeders iniciais
make seed

# 8. Limpar caches
make cache-clear
```

### Forçar recriação do ambiente

Se precisar recriar o ambiente do zero (sobrescreve o `.env`):

```bash
make setup-force
```

---

## Estrutura do projeto

```
message-scheduler/
├── Makefile                    # todos os comandos do projeto
├── setup.sh                    # script de setup automatizado
├── docker-compose.yml
│
├── nginx/
│   ├── Dockerfile
│   ├── nginx.conf
│   └── conf.d/
│       ├── api.conf            # api.localhost.com → backend
│       └── app.conf            # app.localhost.com → frontend
│
├── backend/                    # API Laravel
│   ├── Dockerfile
│   ├── docker/
│   │   └── supervisor/
│   │       └── supervisord.conf
│   ├── src/                    # domínios DDD
│   │   ├── Dispatch/
│   │   ├── Template/
│   │   ├── Tenant/
│   │   ├── BlockList/
│   │   ├── Notification/
│   │   └── Shared/
│   └── tests/
│       ├── Unit/
│       └── Feature/
│
└── frontend/                   # a ser especificado
    └── Dockerfile
```

---

## Serviços e URLs

| Serviço | URL | Notas |
|---|---|---|
| Frontend | http://app.localhost.com | a ser especificado |
| API | http://api.localhost.com | backend Laravel |
| Health Check | http://api.localhost.com/health | valida DB, Redis, Horizon e Huggy |
| Horizon | http://api.localhost.com/horizon | monitoramento de filas (protegido) |
| Telescope | http://api.localhost.com/telescope | inspeção dev/staging (desabilitado em prod) |
| PostgreSQL | localhost:5432 | acesso direto ao banco |
| Redis | localhost:6379 | acesso direto ao cache/fila |

> Horizon e Scheduler sobem **automaticamente** via Supervisor dentro do container backend. Não é necessário iniciá-los manualmente.

---

## Variáveis de ambiente

Todas as variáveis ficam em `backend/.env`. O arquivo `backend/.env.example` contém os valores padrão com comentários.

### Variáveis obrigatórias

| Variável | Descrição |
|---|---|
| `HUGGY_CLIENT_ID` | Client ID do OAuth2 da Huggy |
| `HUGGY_CLIENT_SECRET` | Client Secret do OAuth2 da Huggy |
| `HUGGY_REDIRECT_URI` | URI de callback OAuth2 |
| `DB_PASSWORD` | Senha do PostgreSQL |
| `APP_KEY` | Gerado automaticamente pelo setup |

### Variáveis opcionais (produção)

| Variável | Descrição |
|---|---|
| `SENTRY_LARAVEL_DSN` | DSN do Sentry para captura de erros |
| `REDIS_PASSWORD` | Senha do Redis (se aplicável) |

### Referência completa

```env
APP_NAME=MessageScheduler
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://api.localhost.com

# Huggy OAuth2
HUGGY_CLIENT_ID=
HUGGY_CLIENT_SECRET=
HUGGY_REDIRECT_URI=http://api.localhost.com/auth/callback
HUGGY_API_BASE_URL=https://api.huggy.app

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=message_scheduler
DB_USERNAME=postgres
DB_PASSWORD=

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=

# Filas
QUEUE_CONNECTION=redis
HORIZON_PREFIX=message_scheduler

# Sentry (produção)
SENTRY_LARAVEL_DSN=
```

---

## Comandos disponíveis

Todos os comandos são executados via `make` na raiz do monorepo.

### Ambiente

| Comando | Descrição |
|---|---|
| `make setup` | Setup completo do zero (primeira vez) |
| `make setup-force` | Reconfigura sobrescrevendo `.env` e caches |
| `make up` | Sobe todos os serviços |
| `make down` | Para todos os serviços |
| `make restart` | Reinicia todos os serviços |
| `make build` | Rebuilda todas as imagens Docker |
| `make logs` | Logs de todos os serviços (follow) |
| `make logs-backend` | Logs apenas do backend |
| `make hosts` | Adiciona entradas no `/etc/hosts` (requer sudo) |

### Backend

| Comando | Descrição |
|---|---|
| `make install` | Instala dependências PHP |
| `make migrate` | Roda migrations de negócio |
| `make migrate-landlord` | Roda migrations do landlord |
| `make seed` | Roda seeders |
| `make rollback` | Desfaz a última migration |
| `make cache-clear` | Limpa todos os caches |
| `make tenants-migrate` | Roda migrations para todos os tenants |
| `make tinker` | Abre o Laravel Tinker |
| `make health` | Verifica o health check da API |

### Horizon e Supervisor

| Comando | Descrição |
|---|---|
| `make horizon-status` | Status do Horizon |
| `make horizon-pause` | Pausa o processamento de jobs |
| `make horizon-resume` | Retoma o processamento |
| `make supervisor-status` | Status de todos os processos (Horizon + Scheduler) |
| `make supervisor-logs-horizon` | Logs do Horizon via Supervisor |
| `make supervisor-logs-scheduler` | Logs do Scheduler via Supervisor |

### Testes

| Comando | Descrição |
|---|---|
| `make test` | Roda todos os testes |
| `make test-unit` | Roda apenas testes unitários |
| `make test-feature` | Roda apenas testes de feature |
| `make test-cover` | Testes com cobertura de código (HTML em `coverage/`) |

### Qualidade de código

| Comando | Descrição |
|---|---|
| `make lint` | Verifica estilo (dry-run, sem alterar arquivos) |
| `make lint-fix` | Aplica correções de estilo automaticamente |
| `make analyse` | Roda PHPStan (nível 8) |

---

## Arquitetura

### Multi-tenancy

O sistema usa **single database** com isolamento por `tenant_id`, gerenciado pelo pacote `spatie/laravel-multitenancy`. O tenant ativo é determinado pela sessão do usuário autenticado.

Ao trocar de empresa, todas as tasks são executadas automaticamente:
- **PrefixCacheTask** — isola o cache por tenant
- **SwitchTenantTimezoneTask** — aplica o timezone da empresa ativa

Datas são sempre armazenadas em **UTC** e convertidas para o timezone do tenant na exibição.

### Permissões

As permissões são gerenciadas pelo pacote `spatie/laravel-permission`. O sistema possui dois roles fixos:

| Role | Permissões |
|---|---|
| `admin` | Acesso total ao tenant: gerenciar usuários, configurações, templates compartilhados, histórico de todos e lista de bloqueio |
| `operator` | Criar e cancelar seus próprios disparos, gerenciar seus próprios templates |

### Domain Driven Design

O backend é organizado por domínios em `src/`:

- **Dispatch** — criação, agendamento, recorrência e histórico de disparos
- **Template** — templates pessoais e compartilhados com wildcards
- **Tenant** — seleção de empresa, configurações e gerenciamento de usuários
- **BlockList** — lista de contatos bloqueados (opt-out)
- **Notification** — notificações email e in-app
- **Shared** — serviços compartilhados (HuggyApiService, WildcardResolverService)

Domínios se comunicam via **Events**, nunca importando classes diretamente uns dos outros.

### Processos em background

Horizon e Scheduler são gerenciados pelo **Supervisor** dentro do container backend. Sobem automaticamente com o container e são reiniciados automaticamente em caso de falha.

---

## Testes

Toda funcionalidade deve ter testes cobrindo o **happy path** e os principais **edge cases**. Não é aceito merge sem testes.

```bash
# rodar todos os testes
make test

# rodar com cobertura
make test-cover
# relatório disponível em backend/coverage/index.html
```

Os testes são escritos com **Pest PHP** e organizados espelhando os domínios de `src/`:

```
tests/
├── Unit/        # lógica isolada (Actions, Services)
└── Feature/     # fluxos HTTP completos com banco de dados
    ├── Dispatch/
    ├── Template/
    ├── Tenant/      # inclui teste obrigatório de isolamento entre tenants
    └── BlockList/
```

---

## Observabilidade

### Laravel Telescope

Disponível em ambientes `local` e `staging` em [http://api.localhost.com/telescope](http://api.localhost.com/telescope). Monitora requests, jobs, queries SQL, cache e exceções.

### Sentry

Ativo em produção. Configure `SENTRY_LARAVEL_DSN` no `.env` para captura automática de erros.

### Logs estruturados

Todos os jobs de disparo emitem logs em JSON com `tenant_id`, `dispatch_id`, `contact_id`, `status`, `attempt` e `duration_ms`. Úteis para rastreamento em produção.

### Health Check

```bash
make health
# ou diretamente:
curl http://api.localhost.com/health
```

Retorna `200 OK` com status de cada dependência (PostgreSQL, Redis, Horizon, API Huggy) ou `503` em caso de falha.

---

## Contribuindo

1. Crie uma branch a partir de `main`: `git checkout -b feat/nome-da-feature`
2. Implemente a feature com testes (happy path + edge cases)
3. Verifique estilo e análise estática: `make lint && make analyse`
4. Rode todos os testes: `make test`
5. Abra um Pull Request