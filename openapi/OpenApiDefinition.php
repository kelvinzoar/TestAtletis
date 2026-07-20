<?php

namespace app\openapi;

use OpenApi\Attributes as OA;

/**
 * Definição global do documento OpenAPI.
 *
 * Esta classe não tem lógica: serve apenas de "âncora" para os atributos de
 * nível superior do OpenAPI (informações da API, servidor, esquema de segurança
 * e tags). O swagger-php varre estes atributos ao gerar o openapi.json.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'API de Despesas Pessoais',
    description: 'API RESTful para gerenciamento de despesas pessoais (Yii2 + JWT).'
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Ambiente local (Docker)'
)]
// Define o esquema de autenticação: token JWT no header Authorization: Bearer <token>.
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Tag(name: 'Auth', description: 'Registro e autenticação de usuários')]
#[OA\Tag(name: 'Expenses', description: 'Gerenciamento de despesas do usuário autenticado')]
class OpenApiDefinition
{
}
