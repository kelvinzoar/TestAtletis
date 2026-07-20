<?php

namespace app\openapi;

use OpenApi\Attributes as OA;

/** Schema OpenAPI do corpo de criação/edição de despesa. */
#[OA\Schema(
    schema: 'ExpenseInput',
    title: 'Entrada de despesa',
    required: ['description', 'category', 'amount', 'expense_date'],
    properties: [
        new OA\Property(property: 'description', type: 'string', maxLength: 255, example: 'Almoço no centro'),
        new OA\Property(property: 'category', type: 'string', enum: ['alimentacao', 'transporte', 'lazer'], example: 'alimentacao'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 42.90),
        new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2026-07-10'),
    ]
)]
class ExpenseInputSchema
{
}
