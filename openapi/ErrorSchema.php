<?php

namespace app\openapi;

use OpenApi\Attributes as OA;

/** Schema OpenAPI de erro genérico (401, 404, etc.). */
#[OA\Schema(
    schema: 'Error',
    title: 'Erro genérico',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Not Found'),
        new OA\Property(property: 'message', type: 'string', example: 'Despesa não encontrada.'),
        new OA\Property(property: 'status', type: 'integer', example: 404),
    ]
)]
class ErrorSchema
{
}
