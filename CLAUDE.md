# MessageScheduler

Sistema web multi-tenant de agendamento de mensagens que consome a API REST pública da plataforma Huggy. Permite disparos imediatos, agendados e recorrentes para múltiplos contatos, com templates, wildcards, controle de cadência, observabilidade e isolamento completo por empresa (tenant).

---

## Estrutura do monorepo

```
message-scheduler/               # raiz do monorepo
├── Makefile                     # todos os comandos do projeto
├── setup.sh                     # script de setup inicial do ambiente
├── docker-compose.yml           # orquestração dos serviços
├── nginx/
│   ├── Dockerfile
│   ├── nginx.conf               # configuração base
│   └── conf.d/
│       ├── app.conf             # server block para app.localhost.com → frontend
│       └── api.conf             # server block para api.localhost.com → backend
├── backend/                     # este projeto (Laravel)
│   ├── Dockerfile
│   └── ...
└── frontend/                    # a ser especificado
    └── Dockerfile
```

### Docker Compose — serviços

| Serviço    | Descrição                                   | Porta exposta |
|------------|---------------------------------------------|---------------|
| `nginx`    | Proxy reverso por subdomínio                | 80 / 443      |
| `backend`  | Laravel 13 (PHP 8.4 + FPM)                  | interno       |
| `frontend` | A definir                                   | interno       |
| `postgres` | PostgreSQL 16                               | 5432          |
| `redis`    | Redis (filas + cache)                       | 6379          |

O nginx roteia por **subdomínio**:

| Host                  | Destino    |
|-----------------------|------------|
| `app.localhost.com`   | frontend   |
| `api.localhost.com`   | backend    |

### Makefile

Todos os comandos do projeto são executados via `make`. Nunca documente um comando que não esteja no Makefile.

```makefile
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
```

---

## Nginx — configuração de roteamento

O nginx usa **virtual hosts por subdomínio**. Cada subdomínio tem seu próprio server block em `nginx/conf.d/`.

### `nginx/conf.d/api.conf` — backend

```nginx
server {
    listen 80;
    server_name api.localhost.com;

    location / {
        proxy_pass         http://backend:9000;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}
```

### `nginx/conf.d/app.conf` — frontend

```nginx
server {
    listen 80;
    server_name app.localhost.com;

    location / {
        proxy_pass         http://frontend:3000;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}
```

### Desenvolvimento local

Adicione as entradas abaixo no `/etc/hosts` da sua máquina:

```
127.0.0.1   app.localhost.com
127.0.0.1   api.localhost.com
```

---

## Stack — backend

- **Framework**: Laravel 13
- **Linguagem**: PHP 8.4
- **Multi-tenancy**: spatie/laravel-multitenancy v4
- **Permissões**: spatie/laravel-permission v6
- **Filas**: Laravel Queue + Redis (Laravel Horizon)
- **Agendamento**: Laravel Scheduler
- **Banco de dados**: PostgreSQL 16
- **Cache**: Redis
- **Observabilidade (dev/staging)**: Laravel Telescope + Clockwork
- **Observabilidade (produção)**: Sentry
- **Testes**: Pest PHP
- **Análise estática**: PHPStan (nível 8)
- **Estilo**: Laravel Pint
- **Gerenciador de processos**: Supervisor (dentro do container backend)
- **Documentação da API**: Scramble (dedoc/scramble)

---

## Documentação da API — Scramble

Toda a documentação da API é gerada automaticamente pelo [Scramble](https://scramble.dedoc.co/). Nenhuma anotação manual é necessária — o Scramble infere os schemas a partir dos Form Requests, API Resources e tipos de retorno.

### Endpoints

| URL | Descrição |
|-----|-----------|
| `http://api.localhost.com/docs/api` | UI interativa (Stoplight Elements) |
| `http://api.localhost.com/docs/api.json` | Especificação OpenAPI 3.1 (JSON) |

### Acesso

Em `local` e `staging` os endpoints de docs são abertos. Em `production`, o middleware `RestrictedDocsAccess` bloqueia o acesso por padrão — libere explicitamente por IP ou role conforme necessário.

### Configuração relevante (`config/scramble.php`)

```php
'api_path'   => 'api',                         // prefixo das rotas documentadas
'api_domain' => env('API_DOMAIN', 'api.localhost.com'),
'middleware' => ['web', RestrictedDocsAccess::class],
```

Adicione `API_DOMAIN` ao `.env` de cada ambiente:

```env
API_DOMAIN=api.localhost.com   # local
API_DOMAIN=api.seudominio.com  # produção
```

### Boas práticas

- Nomeie as rotas de API (`->name('dispatches.index')`) — o Scramble usa o nome como `operationId`.
- Documente respostas de erro com `@response` apenas quando o Scramble não conseguir inferi-las automaticamente.
- Nunca escreva anotações OpenAPI manualmente se o Scramble já gera o schema corretamente.

---

## Arquitetura — Domain Driven Design

O backend segue DDD. Cada domínio é autocontido com sua própria lógica, models, actions, events e contratos.

```
backend/
├── src/
│   ├── Dispatch/
│   │   ├── Actions/
│   │   │   ├── CreateDispatchAction.php
│   │   │   ├── CancelDispatchAction.php
│   │   │   ├── RetryDispatchAction.php
│   │   │   └── RetryDispatchBatchAction.php
│   │   ├── Data/
│   │   │   └── CreateDispatchData.php
│   │   ├── Events/
│   │   │   ├── DispatchCreated.php
│   │   │   ├── DispatchCancelled.php
│   │   │   └── DispatchCompleted.php
│   │   ├── Exceptions/
│   │   │   ├── OutsideDispatchWindowException.php
│   │   │   └── DuplicateDispatchException.php
│   │   ├── Jobs/
│   │   │   └── SendMessageJob.php
│   │   ├── Models/
│   │   │   ├── Dispatch.php
│   │   │   └── DispatchContact.php
│   │   ├── Policies/
│   │   │   └── DispatchPolicy.php
│   │   └── Http/
│   │       ├── Controllers/
│   │       │   └── DispatchController.php
│   │       ├── Requests/
│   │       │   ├── CreateDispatchRequest.php
│   │       │   └── UpdateDispatchRequest.php
│   │       └── Resources/
│   │           ├── DispatchResource.php
│   │           └── DispatchContactResource.php
│   │
│   ├── Template/
│   │   ├── Actions/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Http/
│   │
│   ├── Tenant/
│   │   ├── Actions/
│   │   ├── Models/
│   │   │   └── Tenant.php
│   │   ├── Tasks/
│   │   │   └── SwitchTenantTimezoneTask.php
│   │   ├── TenantFinder/
│   │   │   └── UserTenantFinder.php
│   │   └── Http/
│   │
│   ├── BlockList/
│   │   ├── Actions/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Http/
│   │
│   ├── Notification/
│   │   ├── Notifications/
│   │   └── Http/
│   │
│   └── Shared/
│       ├── Services/
│       │   ├── HuggyApiService.php
│       │   ├── WildcardResolverService.php
│       │   └── DuplicateCheckerService.php
│       └── Exceptions/
│           ├── HuggyApiException.php
│           ├── HuggyRateLimitException.php
│           └── HuggyUnavailableException.php
│
└── tests/
    ├── Unit/
    │   ├── Dispatch/
    │   ├── Template/
    │   └── Shared/
    └── Feature/
        ├── Dispatch/
        ├── Template/
        ├── Tenant/
        ├── BlockList/
        └── Notification/
```

**Regras de dependência:**
- Domínios comunicam-se via Events, nunca importando classes diretamente uns dos outros
- Dependências compartilhadas ficam em `Shared/`
- `app/` contém apenas providers, middleware e configuração do framework
- O namespace `Src\` é registrado via `composer.json` apontando para `src/`

---

## Multi-tenancy — spatie/laravel-multitenancy v4

Estratégia: **single database**, isolamento por `tenant_id`.

### Tenant Finder por sessão

```php
// src/Tenant/TenantFinder/UserTenantFinder.php
class UserTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $tenantId = $request->session()->get('active_tenant_id');
        return $tenantId ? Tenant::find($tenantId) : null;
    }
}
```

Registre em `config/multitenancy.php`:
```php
'tenant_finder' => \Src\Tenant\TenantFinder\UserTenantFinder::class,
```

### Tasks

```php
'switch_tenant_tasks' => [
    \Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
    \Src\Tenant\Tasks\SwitchTenantTimezoneTask::class,
],
```

A `SwitchTenantTimezoneTask` aplica `config(['app.timezone' => $tenant->timezone])` e restaura para `UTC` ao `forgetCurrent()`.

### Model Tenant

```php
// src/Tenant/Models/Tenant.php
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $fillable = [
        'name', 'timezone',
        'dispatch_window_start', 'dispatch_window_end',
        'daily_dispatch_limit', 'min_cadence_minutes', 'duplicate_window_hours',
    ];
}
```

### Models de negócio

Usam `UsesTenantConnection` do Spatie:

```php
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Dispatch extends Model
{
    use UsesTenantConnection;
}
```

### Filas tenant-aware

Jobs de disparo implementam `TenantAware`:

```php
use Spatie\Multitenancy\Jobs\TenantAware;

class SendMessageJob implements ShouldQueue, TenantAware { }
```

Configure com opt-in:
```php
'queues_are_tenant_aware_by_default' => false,
'tenant_aware_jobs' => [\Src\Dispatch\Jobs\SendMessageJob::class],
```

### Middleware

```php
Route::middleware(['auth', 'needsTenant'])->group(fn() => [
    // todas as rotas de negócio
]);
```

---

## Permissões — spatie/laravel-permission v6

As permissões são gerenciadas pelo pacote `spatie/laravel-permission`. Roles e permissions são armazenados no banco e atribuídos dinamicamente.

### Roles e permissões do sistema

O seeder de landlord deve criar os dois roles e todas as permissões:

```php
// Roles
Role::create(['name' => 'admin']);
Role::create(['name' => 'operator']);

// Permissões de Dispatch
Permission::create(['name' => 'dispatch.create']);
Permission::create(['name' => 'dispatch.view-own']);
Permission::create(['name' => 'dispatch.view-all']);
Permission::create(['name' => 'dispatch.cancel-own']);
Permission::create(['name' => 'dispatch.cancel-any']);
Permission::create(['name' => 'dispatch.retry']);

// Permissões de Template
Permission::create(['name' => 'template.create']);
Permission::create(['name' => 'template.edit-own']);
Permission::create(['name' => 'template.delete-own']);
Permission::create(['name' => 'template.manage-shared']);

// Permissões administrativas do tenant
Permission::create(['name' => 'tenant.manage-settings']);
Permission::create(['name' => 'tenant.manage-users']);
Permission::create(['name' => 'tenant.manage-block-list']);
Permission::create(['name' => 'tenant.view-audit-log']);

// Atribuição ao role admin
$admin->givePermissionTo(Permission::all());

// Atribuição ao role operator
$operator->givePermissionTo([
    'dispatch.create',
    'dispatch.view-own',
    'dispatch.cancel-own',
    'dispatch.retry',
    'template.create',
    'template.edit-own',
    'template.delete-own',
]);
```

### Trait HasRoles no model User

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

### Como verificar permissões

Sempre use `$user->can('permission.name')` ou `$this->authorize()` via Policy. O Spatie registra todas as permissions automaticamente no Gate do Laravel.

```php
// em qualquer lugar
$user->can('dispatch.view-all');

// em controller (via Policy)
$this->authorize('viewAll', Dispatch::class);

// em middleware de rota
Route::middleware('can:tenant.manage-settings')->group(...);
```

**Nunca** use `$user->hasRole('admin')` para controlar acesso a funcionalidades — use permissões granulares. Roles são apenas agrupadores de permissões.

### Policies integradas com Spatie Permission

Policies continuam sendo usadas para encapsular a lógica de autorização por model. Internamente, elas consultam as permissions do Spatie:

```php
class DispatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dispatch.view-own') || $user->can('dispatch.view-all');
    }

    public function view(User $user, Dispatch $dispatch): bool
    {
        if ($user->can('dispatch.view-all')) {
            return true;
        }

        return $user->can('dispatch.view-own') && $user->id === $dispatch->user_id;
    }

    public function cancel(User $user, Dispatch $dispatch): bool
    {
        if ($user->can('dispatch.cancel-any')) {
            return true;
        }

        return $user->can('dispatch.cancel-own')
            && $user->id === $dispatch->user_id
            && $dispatch->status === DispatchStatus::Pending;
    }
}
```

### Gates para ações de tenant sem model

```php
// app/Providers/AppServiceProvider.php
Gate::define('tenant.manage-settings',  fn(User $u) => $u->can('tenant.manage-settings'));
Gate::define('tenant.manage-users',     fn(User $u) => $u->can('tenant.manage-users'));
Gate::define('tenant.view-audit-log',   fn(User $u) => $u->can('tenant.view-audit-log'));
Gate::define('tenant.manage-block-list',fn(User $u) => $u->can('tenant.manage-block-list'));
```

---

## Timezone por tenant

`APP_TIMEZONE` sempre `UTC`. A `SwitchTenantTimezoneTask` ajusta o timezone por request/job.

- **Armazenamento**: sempre UTC no banco
- **Validação**: converta para o timezone do tenant antes de validar janela de disparo
- **Exibição**: Resources convertem UTC → timezone do tenant antes de formatar
- **Recorrências**: calcule a próxima execução no horário do tenant

```php
// Validação de janela
$hour = (int) $scheduledAt->setTimezone($tenant->timezone)->format('H');
if ($hour < $tenant->dispatch_window_start || $hour >= $tenant->dispatch_window_end) {
    throw new OutsideDispatchWindowException();
}

// Resource
'scheduled_at' => $this->scheduled_at
    ?->setTimezone(Tenant::current()->timezone)
    ->toIso8601String(),
```

---

## Convenções de código

### Actions

Toda operação de negócio é uma **Action** com método único `handle()`. Recebe um DTO, nunca um `Request`. Retorna o modelo ou resultado. Pode disparar Events.

Todo método de controller deve delegar sua lógica para uma Action dentro do respectivo domínio. Controllers não contêm lógica de negócio — apenas recebem a request, chamam a Action e retornam o Resource.

### Form Requests

Um Request por operação. O `authorize()` delega para a Policy:

```php
public function authorize(): bool
{
    return $this->user()->can('create', Dispatch::class);
}
```

### API Resources

Toda resposta de model passa por um Resource. Nunca retorne Eloquent diretamente. Datas em ISO 8601 no timezone do tenant.

### Queries Eloquent

Toda interação com o banco via Eloquent deve usar `Model::query()` explicitamente. Nunca use métodos estáticos diretos como `Model::find()`, `Model::where()`, `Model::create()`, etc.

```php
// correto
Tenant::query()->find($id);
User::query()->where('email', $email)->first();
Dispatch::query()->create([...]);

// errado
Tenant::find($id);
User::where('email', $email)->first();
Dispatch::create([...]);
```

### HuggyApiService

Único ponto de comunicação com a API Huggy. Nunca faça chamadas HTTP em controllers, actions ou jobs diretamente. Erros são convertidos em exceções tipadas (`HuggyApiException`, `HuggyRateLimitException`, `HuggyUnavailableException`).

### WildcardResolverService

Resolução de wildcards ocorre **no job**, não na criação do disparo. Wildcards disponíveis: `{{nome_contato}}`, `{{nome_agente}}`, `{{nome_empresa}}`, `{{canal}}`, e campos customizados da Huggy. Fallback configurável por disparo.

---

## Testes

**Toda funcionalidade deve ter ao menos um teste de feature ou unitário** cobrindo o happy path e os principais edge cases. Não faça merge de código sem testes.

### Como rodar os testes

Sempre use `composer test` para rodar os testes do backend — nunca `php artisan test` diretamente. O script `composer test` executa em sequência: lint (Pint), análise estática (PHPStan nível 8) e a suíte completa de testes (Feature + Unit).

```bash
docker compose exec backend composer test
```

### Estrutura

```
tests/
├── Unit/
│   ├── Dispatch/
│   │   ├── CreateDispatchActionTest.php
│   │   └── WildcardResolverServiceTest.php
│   └── Shared/
│       └── HuggyApiServiceTest.php
└── Feature/
    ├── Dispatch/
    │   ├── CreateDispatchTest.php       # happy path + edge cases de criação
    │   ├── CancelDispatchTest.php
    │   ├── RetryDispatchTest.php
    │   └── SendMessageJobTest.php
    ├── Template/
    │   └── TemplateManagementTest.php
    ├── Tenant/
    │   ├── TenantIsolationTest.php      # OBRIGATÓRIO: vazamento entre tenants
    │   └── SwitchTenantTest.php
    └── BlockList/
        └── BlockListTest.php
```

### Convenções de testes

Use **Pest PHP**. Organize com `describe` por funcionalidade e `it` por caso:

```php
describe('CreateDispatchAction', function () {

    it('creates a dispatch with immediate send', function () {
        // happy path
    });

    it('creates a dispatch scheduled for the future', function () {
        // happy path agendado
    });

    it('throws OutsideDispatchWindowException when scheduled outside allowed hours', function () {
        // edge case: janela de horário
    });

    it('throws DuplicateDispatchException when same content is sent within window', function () {
        // edge case: duplicidade
    });

    it('does not dispatch to contacts on the block list', function () {
        // edge case: opt-out
    });

    it('resolves wildcards individually per contact', function () {
        // edge case: wildcard sem valor usa fallback
    });
});
```

### Edge cases obrigatórios por domínio

**Dispatch:**
- Agendamento fora da janela de horário do tenant → `OutsideDispatchWindowException`
- Disparo duplicado dentro da janela de tempo → alerta ou bloqueio
- Contato na lista de bloqueio → envio cancelado, status `blocked` no histórico
- Falha temporária na API Huggy → retentativa com backoff
- Após 3 falhas → dead-letter queue + captura no Sentry
- Operator tenta ver disparo de outro usuário → 403
- Operator tenta cancelar disparo já processado → 403

**Tenant:**
- Tenant A não deve ver dados do Tenant B (teste de isolamento obrigatório)
- Usuário sem tenant na sessão tenta acessar rota protegida → 401/403
- Troca de tenant limpa dados do tenant anterior da sessão

**Permissões:**
- Operator tenta executar ação exclusiva de Admin → 403
- Admin executa todas as ações → 200/201
- Usuário sem role tenta qualquer ação → 403

**Template:**
- Operator tenta editar template de outro usuário → 403
- Operator tenta criar template compartilhado → 403
- Admin cria e exclui template compartilhado → 200/204

### Helpers de teste

Use `$tenant->execute(fn() => ...)` para rodar código no contexto de um tenant:

```php
it('does not leak dispatches between tenants', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $tenantA->execute(fn() => Dispatch::factory()->create());

    $tenantB->execute(function () {
        expect(Dispatch::count())->toBe(0);
    });
});
```

Use `actingAs($user)` com usuário que já tem o role/permissão atribuído via factory ou seeder de teste.

---


---

## Supervisor — gerenciamento de processos

O Horizon e o Scheduler rodam dentro do container `backend` gerenciados pelo **Supervisor**. Não é necessário iniciá-los manualmente — eles sobem automaticamente com o container.

### Configuração do Supervisor

```ini
; backend/docker/supervisor/supervisord.conf

[supervisord]
nodaemon=true
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:horizon]
command=php /var/www/html/artisan horizon
directory=/var/www/html
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/supervisor/horizon.log
stderr_logfile=/var/log/supervisor/horizon-error.log
stopwaitsecs=3600

[program:scheduler]
command=php /var/www/html/artisan schedule:work
directory=/var/www/html
autostart=true
autorestart=true
stdout_logfile=/var/log/supervisor/scheduler.log
stderr_logfile=/var/log/supervisor/scheduler-error.log
```

### Dockerfile do backend

O Supervisor é o processo principal do container (`CMD`):

```dockerfile
FROM php:8.4-fpm

# dependências do sistema
RUN apt-get update && apt-get install -y \
    supervisor curl unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pcntl \
    && rm -rf /var/lib/apt/lists/*

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# configuração do supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN mkdir -p /var/log/supervisor

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### Estrutura de arquivos Docker

```
backend/
└── docker/
    └── supervisor/
        └── supervisord.conf
```

### Comandos de controle do Horizon

Como o Horizon roda via Supervisor, use os comandos Artisan para controlá-lo:

```bash
make horizon-pause    # pausa o processamento de jobs (Horizon continua rodando)
make horizon-resume   # retoma o processamento
make horizon-status   # exibe o status atual
```

Para reiniciar o Horizon (ex: após deploy), sinalize via Artisan — o Supervisor cuida do restart:

```bash
docker compose exec backend php artisan horizon:terminate
# O Supervisor reinicia o processo automaticamente
```

### Logs do Supervisor

```bash
# logs do Horizon
docker compose exec backend tail -f /var/log/supervisor/horizon.log

# logs do Scheduler
docker compose exec backend tail -f /var/log/supervisor/scheduler.log

# status de todos os processos gerenciados
docker compose exec backend supervisorctl status
```

Adicione targets convenientes no Makefile:

```makefile
supervisor-status: ## Status dos processos gerenciados pelo Supervisor
	docker compose exec backend supervisorctl status

supervisor-logs-horizon: ## Logs do Horizon via Supervisor
	docker compose exec backend tail -f /var/log/supervisor/horizon.log

supervisor-logs-scheduler: ## Logs do Scheduler via Supervisor
	docker compose exec backend tail -f /var/log/supervisor/scheduler.log
```

## Observabilidade

### Telescope (dev/staging)

Monitora: requests HTTP, jobs, queries SQL, cache, exceções, Scheduler.

### Sentry (produção)

Captura automática + manual com contexto:

```php
Sentry::captureException($e, ['extra' => [
    'tenant_id'   => Tenant::current()?->id,
    'dispatch_id' => $this->dispatchId,
    'contact_id'  => $this->contactId,
]]);
```

### Logs estruturados (JSON)

Campo obrigatório em todo `SendMessageJob`:

```json
{
  "tenant_id": "uuid", "dispatch_id": "uuid", "contact_id": "uuid",
  "channel_id": "uuid", "status": "sent|failed|blocked",
  "attempt": 1, "duration_ms": 340, "error": null,
  "timestamp": "2026-03-24T10:00:00Z"
}
```

### Retentativas

```
1ª falha → 30s | 2ª falha → 2min | 3ª falha → 10min → dead-letter + Sentry
```

### Health Check

`GET http://api.localhost.com/health` — valida PostgreSQL, Redis, Horizon e API Huggy. Usado pelo CI/CD após cada deploy.

---

## Variáveis de ambiente

```env
# Huggy OAuth2
HUGGY_CLIENT_ID=
HUGGY_CLIENT_SECRET=
HUGGY_REDIRECT_URI=
HUGGY_API_BASE_URL=https://api.huggy.app

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=message_scheduler
DB_USERNAME=
DB_PASSWORD=

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=

# Sentry (produção)
SENTRY_LARAVEL_DSN=

# Filas
QUEUE_CONNECTION=redis
HORIZON_PREFIX=message_scheduler

# App
APP_TIMEZONE=UTC
```

---

## Pontos em aberto

- [ ] Janela de horário: fixa no sistema ou configurável por canal?
- [ ] Agendamento fora da janela: rejeitar ou reagendar automaticamente?
- [ ] Tamanho máximo de arquivo para upload
- [ ] Janela de tempo padrão para duplicidade (sugestão: 24h)
- [ ] Intervalo mínimo de cadência (sugestão: 5 min)
- [ ] Limite diário padrão de disparos por canal
- [ ] Token OAuth2 da Huggy: por usuário ou por usuário+empresa?
- [ ] Formato do CSV de importação de contatos
- [ ] Retenção de logs no Telescope e Sentry
- [ ] Stack do frontend (a definir)