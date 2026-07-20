<?php

namespace app\controllers;

use app\models\forms\ExpenseSearch;
use app\services\ExpenseService;
use OpenApi\Attributes as OA;
use Yii;

/**
 * CRUD de despesas. Todas as actions exigem token JWT (herdado da base).
 *
 * O controller é intencionalmente "magro": ele apenas
 *   1) lê a entrada da requisição,
 *   2) delega ao ExpenseService (onde vivem as regras e a checagem de posse),
 *   3) devolve o resultado.
 * Regras de negócio NÃO ficam aqui — isso é a separação de camadas pedida.
 */
class ExpenseController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        private ExpenseService $expenseService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * GET /expenses
     * Lista as despesas do usuário com filtros (category, month, year),
     * ordenação (sort=asc|desc) e paginação (page, per_page).
     */
    #[OA\Get(
        path: '/expenses',
        tags: ['Expenses'],
        summary: 'Lista as despesas do usuário (com filtros, ordenação e paginação)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['alimentacao', 'transporte', 'lazer'])),
            new OA\Parameter(name: 'year', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
            new OA\Parameter(name: 'month', in: 'query', required: false, description: 'Exige o parâmetro year', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de despesas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Expense')),
                    new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Parâmetros de busca inválidos', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function actionIndex(): array
    {
        $search = new ExpenseSearch();
        $search->load(Yii::$app->request->getQueryParams(), '');

        return $this->expenseService->list($this->currentUser(), $search);
    }

    /**
     * GET /expenses/{id}
     * Detalha uma despesa específica do usuário.
     */
    #[OA\Get(
        path: '/expenses/{id}',
        tags: ['Expenses'],
        summary: 'Detalha uma despesa do usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Despesa encontrada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'expense', ref: '#/components/schemas/Expense'),
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Não encontrada ou de outro usuário', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function actionView(int $id): array
    {
        $expense = $this->expenseService->view($this->currentUser(), $id);
        return ['expense' => $expense];
    }

    /**
     * POST /expenses
     * Cria uma nova despesa.
     */
    #[OA\Post(
        path: '/expenses',
        tags: ['Expenses'],
        summary: 'Cria uma nova despesa',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ExpenseInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Despesa criada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'expense', ref: '#/components/schemas/Expense'),
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Erro de validação', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function actionCreate(): array
    {
        $expense = $this->expenseService->create(
            $this->currentUser(),
            Yii::$app->request->getBodyParams()
        );

        Yii::$app->response->setStatusCode(201);
        return ['expense' => $expense];
    }

    /**
     * PUT/PATCH /expenses/{id}
     * Edita qualquer campo de uma despesa existente do usuário.
     */
    #[OA\Put(
        path: '/expenses/{id}',
        tags: ['Expenses'],
        summary: 'Edita uma despesa do usuário (qualquer campo)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ExpenseInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Despesa atualizada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'expense', ref: '#/components/schemas/Expense'),
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Não encontrada ou de outro usuário', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Erro de validação', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    #[OA\Patch(
        path: '/expenses/{id}',
        tags: ['Expenses'],
        summary: 'Edita uma despesa do usuário (alias PATCH de PUT)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ExpenseInput')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Despesa atualizada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'expense', ref: '#/components/schemas/Expense'),
            ])),
            new OA\Response(response: 404, description: 'Não encontrada ou de outro usuário', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Erro de validação', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function actionUpdate(int $id): array
    {
        $expense = $this->expenseService->update(
            $this->currentUser(),
            $id,
            Yii::$app->request->getBodyParams()
        );

        return ['expense' => $expense];
    }

    /**
     * DELETE /expenses/{id}
     * Exclui uma despesa do usuário. Responde 204 No Content.
     */
    #[OA\Delete(
        path: '/expenses/{id}',
        tags: ['Expenses'],
        summary: 'Exclui uma despesa do usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Excluída (sem corpo)'),
            new OA\Response(response: 401, description: 'Não autenticado', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Não encontrada ou de outro usuário', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function actionDelete(int $id): void
    {
        $this->expenseService->delete($this->currentUser(), $id);
        Yii::$app->response->setStatusCode(204);
    }
}
