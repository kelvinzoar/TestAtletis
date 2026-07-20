<?php

namespace app\openapi;

use OpenApi\Attributes as OA;

/** Schema OpenAPI do usuário (resposta; sem expor password_hash). */
#[OA\Schema(
    schema: 'User',
    title: 'Usuário',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'teste@example.com'),
        new OA\Property(property: 'created_at', type: 'integer', example: 1752969600),
    ]
)]
class UserSchema
{
}
