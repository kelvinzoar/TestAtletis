<?php

namespace app\controllers;

use app\models\forms\ExpenseSearch;
use app\services\ExpenseService;
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
    public function actionView(int $id): array
    {
        $expense = $this->expenseService->view($this->currentUser(), $id);
        return ['expense' => $expense];
    }

    /**
     * POST /expenses
     * Cria uma nova despesa.
     */
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
    public function actionDelete(int $id): void
    {
        $this->expenseService->delete($this->currentUser(), $id);
        Yii::$app->response->setStatusCode(204);
    }
}
