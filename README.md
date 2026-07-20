# API de Gerenciamento de Despesas Pessoais

API RESTful construída com **PHP 8.2 + Yii2**, autenticação **JWT**, banco **MySQL 8** e ambiente **Docker**. Permite que usuários autenticados cadastrem, listem, filtrem, editem e excluam suas despesas pessoais.

---

## Sumário

- [Stack e decisões técnicas](#stack-e-decisões-técnicas)
- [Arquitetura](#arquitetura)
- [Como executar (Docker)](#como-executar-docker)
- [Como rodar os testes](#como-rodar-os-testes)
- [Documentação da API](#documentação-da-api)
- [Estrutura de pastas](#estrutura-de-pastas)

---

## Stack e decisões técnicas

| Item | Escolha | Motivo |
|------|---------|--------|
| Framework | Yii2 (`yiisoft/yii2-app-basic` como base) | Requisito. Usei o template **basic** (não o advanced) por ser mais enxuto para uma **API pura** — o advanced separa frontend/backend/console, o que seria overhead aqui. |
| Autenticação | JWT via `firebase/php-jwt` `^7.0` | O Yii2 **não** possui JWT nativo. Optei por uma biblioteca amplamente adotada e a isolei em um serviço (`JwtService`). Usei a linha **7.x** porque as 6.x têm um security advisory (CVE-2025-45769, LOW); a 7.x exige chave HS256 de no mínimo 32 bytes. |
| Assets | Repositório `asset-packagist` | O core do Yii2 depende de bower-assets (jQuery etc.). Em vez do plugin legado `fxp/composer-asset-plugin`, declarei o repositório `asset-packagist` no `composer.json` — abordagem recomendada atual. |
| Banco | MySQL 8 + Migrations | Requisito. Schema versionado com migrations (equivalente às EF Migrations). |
| Dinheiro | `DECIMAL(10,2)` | Evita erros de arredondamento de ponto flutuante. Nunca `float` para valores monetários. |
| Categoria | `ENUM` no banco + validação no model | Dupla barreira de integridade para o conjunto restrito (alimentação, transporte, lazer). |
| Camadas | Controllers → Services → Models | Regras de negócio ficam nos **serviços**; controllers apenas orquestram. Segue SOLID e a separação pedida no desafio. |
| Erros | `ApiErrorHandler` centralizado | Formato de erro JSON padronizado, incluindo erros de validação por campo (HTTP 422). |
| Testes | Codeception (suíte de API) | Cobre os principais endpoints, incluindo o isolamento de dados entre usuários. |
| Documentação | `zircote/swagger-php` + Swagger UI | Docs interativas em `/docs`, geradas a partir de anotações OpenAPI no código (não desatualizam). Assets do Swagger UI embutidos localmente (funciona offline). |

### Destaques de segurança

- **Isolamento por usuário (anti-IDOR):** toda consulta de despesa é filtrada por `user_id`. Se um usuário tenta acessar a despesa de outro pelo ID, recebe **404** (não revelamos a existência de recursos alheios).
- **Proteção contra mass assignment:** `user_id` não é um atributo atribuível em massa; é definido apenas pelo servidor a partir do token.
- **Senhas** são armazenadas com hash bcrypt (nunca em texto puro) e **jamais** retornadas pela API.
- **Segredos** (JWT, banco) vêm de variáveis de ambiente, fora do código-fonte.

---

## Arquitetura

Fluxo de uma requisição autenticada:

```
Requisição HTTP
   │
   ▼
Nginx  ──►  PHP-FPM (web/index.php)
   │
   ▼
UrlManager (roteamento REST)
   │
   ▼
JwtAuth (valida o token, popula o usuário atual)   ◄── components/
   │
   ▼
Controller (magro: lê entrada, delega)             ◄── controllers/
   │
   ▼
Service (regra de negócio + checagem de posse)     ◄── services/
   │
   ▼
Model / ActiveRecord (validação + persistência)    ◄── models/
   │
   ▼
MySQL
```

Responsabilidades por camada:

- **Controllers**: leem a requisição e delegam. Nenhuma regra de negócio.
- **Services**: `AuthService`, `JwtService`, `ExpenseService`. Onde vivem as regras.
- **Models**: `User`, `Expense` (ActiveRecord + validações) e forms (`RegisterForm`, `LoginForm`, `ExpenseSearch`) como DTOs de entrada.
- **Components**: `JwtAuth` (autenticação) e `ApiErrorHandler` (erros JSON).

> Todo o código está comentado explicando **o porquê** de cada decisão, com analogias ao ecossistema C#/.NET onde ajuda.

---

## Como executar (Docker)

**Pré-requisito:** Docker Desktop instalado e em execução.

```bash
# 1. Copie as variáveis de ambiente
cp .env.example .env

# 2. Suba os containers (PHP, Nginx, MySQL)
docker compose up -d --build

# 3. Instale as dependências do PHP (dentro do container)
docker compose exec php composer install

# 4. Rode as migrations (cria as tabelas user e expense)
docker compose exec php php yii migrate --interactive=0
```

A API fica disponível em: **http://localhost:8080**

Teste rápido:

```bash
# Registrar um usuário
curl -X POST http://localhost:8080/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"teste@example.com","password":"secret123","password_confirm":"secret123"}'

# Login (guarde o token retornado)
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"teste@example.com","password":"secret123"}'

# Criar despesa (troque <TOKEN>)
curl -X POST http://localhost:8080/expenses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"description":"Almoço","category":"alimentacao","amount":42.90,"expense_date":"2026-07-10"}'
```

Para derrubar o ambiente:

```bash
docker compose down        # mantém os dados do banco
docker compose down -v      # apaga também o volume do banco
```

---

## Como rodar os testes

Os testes usam **Codeception** e rodam contra um **banco de teste separado** (`despesas_test`).

```bash
# 1. Crie o banco de teste e dê acesso ao usuário da aplicação
#    (o MySQL só concede privilégios automáticos no banco principal)
docker compose exec mysql mysql -uroot -proot_secret -e \
  "CREATE DATABASE IF NOT EXISTS despesas_test CHARACTER SET utf8mb4; \
   GRANT ALL PRIVILEGES ON despesas_test.* TO 'despesas'@'%'; FLUSH PRIVILEGES;"

# 2. Aplique as migrations no banco de teste
docker compose exec -e DB_NAME=despesas_test php php yii migrate --interactive=0

# 3. Gere as classes auxiliares do Codeception (uma vez)
docker compose exec php vendor/bin/codecept build

# 4. Rode a suíte de API
docker compose exec -e DB_TEST_NAME=despesas_test php vendor/bin/codecept run Api
```

Saída esperada: `OK (7 tests, 15 assertions)`.

Cobrem: registro/login, e-mail duplicado, senha inválida, criação/listagem de despesa, categoria inválida e — o mais importante — **um usuário não consegue acessar a despesa de outro**.

---

## Documentação da API

Duas formas de consultar:

1. **Swagger UI (interativo):** com o ambiente no ar, acesse **http://localhost:8080/docs** — permite testar os endpoints direto do navegador (inclusive autenticando com o token JWT no botão *Authorize*).
2. **Markdown:** a especificação em texto está em **[API.md](API.md)**.

O Swagger UI é gerado a partir de **anotações OpenAPI no próprio código** (atributos PHP 8 nos controllers, pasta `openapi/`). Para regenerar o spec após alterar as anotações:

```bash
docker compose exec php composer swagger
```

> Como usar o token no Swagger UI: rode `POST /auth/login`, copie o `token` da resposta, clique em **Authorize** (cadeado no topo), cole o token e confirme. As rotas protegidas passam a enviar o header automaticamente.

---

## Estrutura de pastas

```
.
├── components/        # JwtAuth (autenticação) e ApiErrorHandler (erros JSON)
├── config/            # web, console, db, params, container (DI), test
├── controllers/       # AuthController, ExpenseController (magros)
├── docker/            # Dockerfile do PHP + config do Nginx
├── exceptions/        # ValidationException (HTTP 422)
├── migrations/        # criação das tabelas user e expense
├── models/            # User, Expense (ActiveRecord)
│   └── forms/         # RegisterForm, LoginForm, ExpenseSearch (DTOs)
├── openapi/           # anotações OpenAPI (definição global + schemas)
├── services/          # AuthService, JwtService, ExpenseService (regra de negócio)
├── tests/             # Codeception (suíte de API)
├── web/               # index.php (front controller)
│   └── docs/          # Swagger UI + openapi.json (servidos estaticamente)
├── yii                # entrada de console (migrations)
├── docker-compose.yml
├── API.md
└── README.md
```
