<?php

namespace app\openapi;

use OpenApi\Attributes as OA;

/** Schema OpenAPI do bloco de paginação da listagem. */
#[OA\Schema(
    schema: 'Pagination',
    title: 'Paginação',
    properties: [
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 2),
        new OA\Property(property: 'page_count', type: 'integer', example: 1),
    ]
)]
class PaginationSchema
{
}
