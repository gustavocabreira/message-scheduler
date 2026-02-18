# Sistema de Agendamento de Mensagens - Especificação do Projeto

## Visão Geral

Desenvolver uma API backend completa para um sistema de agendamento de mensagens que suporta múltiplos providers de envio. O sistema deve permitir que usuários autenticados conectem-se a diferentes plataformas de mensagens (começando com Huggy) e agendem mensagens para serem enviadas em horários específicos.

---

## Stack Tecnológica Obrigatória

### Backend
- **PHP 8.5+** (versão mais recente)
- **Laravel 12.x** (versão mais recente)
- **Laravel Octane com Swoole** (para alta performance)
- **PostgreSQL** (banco de dados principal)
- **Redis** (cache e gerenciamento de filas)

### Infraestrutura
- **Docker** (containerização completa)
- **Docker Compose** (orquestração de todos os serviços)
- **Laravel Horizon** (gerenciamento visual de filas)
- **Supervisor** (gerenciamento de processos: Octane, Horizon, Scheduler)

### Qualidade e Testes
- **PestPHP** (testes unitários e de feature)
- **Laravel Pint** (code styling e formatação)
- **PHPStan + Larastan** (análise estática de código e checagem de tipos)
- **Strict Types** habilitado em TODOS os arquivos PHP (`declare(strict_types=1);`)

---

## Requisitos Funcionais Principais

### 1. Sistema de Autenticação
- Autenticação tradicional com email e senha
- Login social via OAuth2:
  - Google
  - GitHub
- Geração e gerenciamento de tokens de API (Laravel Sanctum)

### 2. Gerenciamento de Providers de Mensagens

#### Primeira Integração: Huggy
- Integração completa com a API v3 da Huggy
- Documentação: https://developers.huggy.io/pt/API/api-v3.html
- Autenticação via OAuth2 e/ou tokens de API

#### Arquitetura Extensível
- Sistema preparado para adicionar novos providers no futuro
- Interface padronizada para comunicação com diferentes providers
- Configurações específicas por provider

#### Funcionalidades do Provider
- Conexão e autenticação com o provider
- Edição de credenciais (tokens de API, OAuth tokens, etc)
- Sincronização de contatos
- Validação de conexão
- Status de conexão (ativo, inativo, erro)

### 3. Sistema de Agendamento de Mensagens

#### Agendamento Individual
- Selecionar contato do provider (Huggy)
- Filtrar e buscar contatos diretamente da API
- Definir conteúdo da mensagem (texto apenas, sem arquivos nesta versão)
- Definir data e hora do envio
- Validação: não permitir agendamento para o passado

#### Agendamento em Bulk
- Agendar mesma mensagem para múltiplos contatos
- Seleção de múltiplos destinatários
- Mesmo horário de envio para todos
- Processamento assíncrono

#### Processamento
- Execução via filas assíncronas
- Workers do Laravel Horizon processando os envios
- Sistema de retry para falhas
- Log de tentativas e erros

### 4. Painel de Gerenciamento

#### Listagem de Agendamentos
- Visualizar todos os agendamentos do usuário
- Filtros:
  - Status (pendente, processando, enviado, falha, cancelado)
  - Data de agendamento
  - Provider
  - Contato
- Paginação
- Ordenação

#### Ações sobre Agendamentos
- Visualizar detalhes completos
- Editar:
  - Contato
  - Conteúdo da mensagem
  - Horário de envio
- Cancelar agendamento
- Histórico de tentativas e logs

### 5. Processamento Assíncrono
- Command rodando a cada minuto via Laravel Scheduler
- Busca mensagens com horário vencido
- Dispatch para fila do Horizon
- Jobs processam envio via API do provider
- Atualização de status e logs

---

## Requisitos Não-Funcionais

### Performance
- Uso de Laravel Octane + Swoole para alta performance
- Cache agressivo com Redis
- Otimização de queries com eager loading
- Índices adequados no banco de dados

### Segurança
- Criptografia de credenciais sensíveis (tokens, senhas)
- Rate limiting em todos os endpoints
- Validação rigorosa de inputs
- Sanitização de outputs
- CORS configurado adequadamente
- Proteção contra state leakage no Octane

### Qualidade de Código
- 100% dos arquivos com `declare(strict_types=1);`
- Type hints em todos os métodos
- Cobertura de testes mínima de 80%
- Code style validado com Laravel Pint (PSR-12)
- Análise estática com PHPStan (nível máximo) + Larastan
- Arquitetura limpa e SOLID

### Infraestrutura
- Todos os serviços rodando em Docker
- Docker Compose orquestrando:
  - App (Laravel Octane)
  - PostgreSQL
  - Redis
  - Horizon
  - Scheduler
- Environment completamente reproduzível
- Health checks em todos os serviços

### Documentação
- README completo com instruções de instalação
- Documentação de API (endpoints, requests, responses)
- Guia de deployment
- Exemplos de uso
- Troubleshooting

---

## Estrutura do Projeto

```
projeto/
├── app/
│   ├── Actions/              # Ações de negócio
│   ├── Data/                 # DTOs
│   ├── Enums/                # Enumeradores
│   ├── Exceptions/           # Exceções customizadas
│   ├── Http/
│   │   ├── Controllers/      # Controllers da API
│   │   ├── Requests/         # Form Requests
│   │   ├── Resources/        # API Resources
│   │   └── Middleware/       # Middlewares customizados
│   ├── Models/               # Models Eloquent
│   ├── Providers/
│   │   └── MessageProviders/ # Implementações de providers
│   ├── Repositories/         # Repositories (opcional)
│   └── Services/             # Serviços de negócio
├── docker/
│   ├── octane/
│   │   ├── Dockerfile
│   │   ├── php.ini
│   │   ├── php.prod.ini
│   │   ├── supervisord.conf
│   │   └── supervisord.prod.conf
│   └── postgres/
├── tests/
│   ├── Feature/              # Testes de feature
│   └── Unit/                 # Testes unitários
├── docker-compose.yml
├── docker-compose.prod.yml
├── phpstan.neon              # Configuração PHPStan
├── Makefile
└── .env.example
```

---

## Endpoints da API (Exemplos)

### Autenticação
- `POST /api/auth/register` - Registrar usuário
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Perfil do usuário
- `GET /api/auth/oauth/{provider}` - Redirect OAuth
- `GET /api/auth/oauth/{provider}/callback` - Callback OAuth

### Providers
- `GET /api/providers` - Listar providers do usuário
- `POST /api/providers` - Criar conexão com provider
- `GET /api/providers/{id}` - Detalhes do provider
- `PUT /api/providers/{id}` - Atualizar credenciais
- `DELETE /api/providers/{id}` - Remover provider
- `POST /api/providers/{id}/test-connection` - Testar conexão
- `POST /api/providers/{id}/sync-contacts` - Sincronizar contatos
- `GET /api/providers/{id}/contacts` - Listar contatos

### Agendamentos
- `GET /api/scheduled-messages` - Listar agendamentos
- `POST /api/scheduled-messages` - Criar agendamento
- `POST /api/scheduled-messages/bulk` - Criar agendamento em bulk
- `GET /api/scheduled-messages/{id}` - Detalhes do agendamento
- `PUT /api/scheduled-messages/{id}` - Editar agendamento
- `DELETE /api/scheduled-messages/{id}` - Cancelar agendamento
- `GET /api/scheduled-messages/{id}/logs` - Logs do agendamento

### Utilitários
- `GET /api/health` - Health check
- `GET /horizon` - Dashboard do Horizon (protegido)

---

## Fluxo de Trabalho Esperado

1. **Usuário se registra/autentica** no sistema
2. **Conecta um provider** (Huggy) com suas credenciais
3. **Sistema sincroniza contatos** do provider
4. **Usuário cria agendamento** selecionando:
   - Provider conectado
   - Contato(s) destinatário(s)
   - Conteúdo da mensagem
   - Data e hora do envio
5. **Sistema valida** e salva o agendamento
6. **Scheduler (cron) roda a cada minuto** e identifica mensagens vencidas
7. **Dispatcher envia para fila** do Horizon
8. **Worker processa** o job e envia via API do provider
9. **Sistema atualiza status** e registra logs
10. **Usuário visualiza** status no painel de gerenciamento

---

## Considerações Importantes

### Octane + Swoole
- Cuidado com state leakage entre requests
- Usar dependency injection adequadamente
- Configurar listeners para cleanup
- Não usar variáveis globais ou estáticas com estado
- Testar comportamento concurrent

### Filas e Jobs
- Jobs devem ser stateless
- Implementar retry logic
- Timeout adequados
- Failed job handlers
- Logs detalhados

### Testes
- Testar em ambiente Docker (não localmente)
- Mockar APIs externas (Huggy)
- Testar concurrency com Octane
- Testar graceful shutdown
- Testar recovery de failures

### Análise Estática
- PHPStan configurado no nível máximo (level 9)
- Larastan para regras específicas do Laravel
- Executar análise antes de cada commit
- Zero erros de análise estática permitidos
- Integrar no CI/CD pipeline

### Segurança
- Nunca commitar .env
- Usar secrets management em produção
- Manter Docker images atualizadas
- Configurar network isolation
- Rate limiting agressivo

---

## IMPORTANTE: ANTES DE COMEÇAR

**Você deve me fazer 10 perguntas sobre o projeto antes de iniciar a implementação.**

Estas perguntas devem esclarecer:
- Detalhes de implementação que não estão claros
- Decisões de arquitetura que precisam ser definidas
- Requisitos funcionais que podem ter múltiplas interpretações
- Preferências de design e padrões
- Prioridades de desenvolvimento
- Casos de uso específicos que não foram cobertos
- Integrações e dependências
- Performance e escalabilidade
- Segurança e compliance
- Qualquer outro aspecto crítico do projeto

**Não comece a implementação até que todas as 10 perguntas sejam respondidas.**

Esta é uma oportunidade para garantir que temos um entendimento compartilhado completo do escopo do projeto antes de escrever qualquer código.

## 📋 IMPORTANTE: Tracking de Progresso

Você DEVE atualizar o arquivo PROJECT_STATUS.md após:
1. Completar qualquer tarefa
2. Criar novos arquivos
3. Passar validações
4. Encontrar problemas

Formato de atualização:
- Marque [x] tarefas completas
- Atualize porcentagens
- Adicione timestamp
- Registre problemas encontrados
- Atualize "Próximos Passos"

Sempre mostre o status atualizado após cada etapa.