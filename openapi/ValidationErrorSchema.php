<?php

namespace app\openapi;

use OpenApi\Attributes as OA;

/** Schema OpenAPI da resposta de erro de validação (HTTP 422). */
#[OA\Schema(
    schema: 'ValidationError',
    title: 'Erro de validação (422)',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Unprocessable Entity'),
        new OA\Property(property: 'message', type: 'string', example: 'Os dados enviados são inválidos.'),
        new OA\Property(property: 'status', type: 'integer', example: 422),
        new OA\Property(
            property: 'errors',
            type: 'object',
            description: 'Mapa campo -> lista de mensagens',
            example: ['category' => ['Categoria inválida. Use: alimentacao, transporte, lazer.']]
        ),
    ]
)]
class ValidationErrorSchema
{
}
