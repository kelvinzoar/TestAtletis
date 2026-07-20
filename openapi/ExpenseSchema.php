<?php

namespace app\openapi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI da despesa (resposta). Classe apenas "porta-atributos".
 * Cada schema fica em seu próprio arquivo por causa do autoload PSR-4 usado
 * pelo swagger-php ao analisar as classes.
 */
#[OA\Schema(
    schema: 'Expense',
    title: 'Despesa',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'description', type: 'string', example: 'Almoço no centro'),
        new OA\Property(property: 'category', type: 'string', enum: ['alimentacao', 'transporte', 'lazer'], example: 'alimentacao'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 42.90),
        new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2026-07-10'),
        new OA\Property(property: 'created_at', type: 'integer', example: 1752969600),
        new OA\Property(property: 'updated_at', type: 'integer', example: 1752969600),
    ]
)]
class ExpenseSchema
{
}
