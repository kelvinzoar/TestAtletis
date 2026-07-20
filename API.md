# Especificação da API

Base URL (ambiente local): `http://localhost:8080`

Todas as requisições e respostas usam **JSON** (`Content-Type: application/json`).

## Autenticação

A API usa **JWT (Bearer Token)**. Após o login, envie o token no header de todas as rotas protegidas:

```
Authorization: Bearer <token>
```

Rotas públicas: `POST /auth/register` e `POST /auth/login`. Todas as demais exigem token válido.

## Formato de erros

| Código | Significado |
|--------|-------------|
| `401 Unauthorized` | Token ausente/inválido/expirado, ou credenciais incorretas. |
| `404 Not Found` | Recurso inexistente **ou que não pertence ao usuário autenticado**. |
| `422 Unprocessable Entity` | Erro de validação. Inclui o objeto `errors` com detalhes por campo. |

Exemplo de erro de validação (422):

```json
{
    "name": "Unprocessable Entity",
    "message": "Os dados enviados são inválidos.",
    "code": 0,
    "status": 422,
    "errors": {
        "category": ["Categoria inválida. Use: alimentacao, transporte, lazer."],
        "amount": ["Valor não pode ser menor que 0.01."]
    }
}
```

---

## Endpoints

### 1. Registrar usuário

`POST /auth/register` — público

**Body**

| Campo | Tipo | Obrigatório | Regras |
|-------|------|:-----------:|--------|
| `email` | string | sim | e-mail válido, único |
| `password` | string | sim | mínimo 6 caracteres |
| `password_confirm` | string | sim | deve ser igual a `password` |

**Resposta `201 Created`**

```json
{
    "user": {
        "id": 1,
        "email": "teste@example.com",
        "created_at": 1752969600
    }
}
```

---

### 2. Login

`POST /auth/login` — público

**Body**

| Campo | Tipo | Obrigatório |
|-------|------|:-----------:|
| `email` | string | sim |
| `password` | string | sim |

**Resposta `200 OK`**

```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 3600,
    "user": {
        "id": 1,
        "email": "teste@example.com",
        "created_at": 1752969600
    }
}
```

Erro de credenciais → `401 Unauthorized`.

---

### 3. Listar despesas

`GET /expenses` — **requer token**

Retorna apenas as despesas do usuário autenticado, com filtros, ordenação e paginação.

**Query params**

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `category` | string | — | Filtra por categoria (`alimentacao`, `transporte`, `lazer`). |
| `year` | int | — | Filtra por ano (ex.: `2026`). |
| `month` | int | — | Filtra por mês (`1`–`12`). **Exige `year`.** |
| `sort` | string | `desc` | Ordena por data da despesa: `asc` ou `desc`. |
| `page` | int | `1` | Página (começa em 1). |
| `per_page` | int | `15` | Itens por página (máx. 100). |

**Exemplo:** `GET /expenses?category=alimentacao&year=2026&month=7&sort=asc&page=1&per_page=10`

**Resposta `200 OK`**

```json
{
    "items": [
        {
            "id": 5,
            "description": "Almoço",
            "category": "alimentacao",
            "amount": 42.90,
            "expense_date": "2026-07-10",
            "created_at": 1752969600,
            "updated_at": 1752969600
        }
    ],
    "pagination": {
        "page": 1,
        "per_page": 10,
        "total": 1,
        "page_count": 1
    }
}
```

---

### 4. Detalhar despesa

`GET /expenses/{id}` — **requer token**

**Resposta `200 OK`**

```json
{
    "expense": {
        "id": 5,
        "description": "Almoço",
        "category": "alimentacao",
        "amount": 42.90,
        "expense_date": "2026-07-10",
        "created_at": 1752969600,
        "updated_at": 1752969600
    }
}
```

Se a despesa não existir **ou for de outro usuário** → `404 Not Found`.

---

### 5. Criar despesa

`POST /expenses` — **requer token**

**Body**

| Campo | Tipo | Obrigatório | Regras |
|-------|------|:-----------:|--------|
| `description` | string | sim | máx. 255 caracteres |
| `category` | string | sim | `alimentacao`, `transporte` ou `lazer` |
| `amount` | decimal | sim | maior que 0 |
| `expense_date` | date | sim | formato `YYYY-MM-DD` |

> `user_id` é definido automaticamente pelo servidor a partir do token — enviá-lo no corpo não tem efeito.

**Resposta `201 Created`** — retorna a despesa criada (mesmo formato do detalhe).

---

### 6. Editar despesa

`PUT /expenses/{id}` ou `PATCH /expenses/{id}` — **requer token**

Permite editar qualquer campo. Aceita os mesmos campos do cadastro.

**Resposta `200 OK`** — retorna a despesa atualizada.

Se a despesa não existir ou for de outro usuário → `404 Not Found`.

---

### 7. Excluir despesa

`DELETE /expenses/{id}` — **requer token**

**Resposta `204 No Content`** (corpo vazio).

Se a despesa não existir ou for de outro usuário → `404 Not Found`.

---

## Resumo das rotas

| Método | Rota | Autenticação | Descrição |
|--------|------|:------------:|-----------|
| POST | `/auth/register` | — | Registrar usuário |
| POST | `/auth/login` | — | Login (retorna JWT) |
| GET | `/expenses` | ✅ | Listar (filtros + ordenação + paginação) |
| GET | `/expenses/{id}` | ✅ | Detalhar |
| POST | `/expenses` | ✅ | Criar |
| PUT/PATCH | `/expenses/{id}` | ✅ | Editar |
| DELETE | `/expenses/{id}` | ✅ | Excluir |
