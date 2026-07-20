<?php

namespace app\services;

use app\exceptions\ValidationException;
use app\models\Expense;
use app\models\forms\ExpenseSearch;
use app\models\User;
use yii\web\NotFoundHttpException;

/**
 * Serviço com as regras de negócio de despesas.
 *
 * Ponto central deste desafio: TODA operação recebe o usuário autenticado e
 * garante que ele só acessa as próprias despesas. Isso evita a falha conhecida
 * como IDOR (acessar recurso alheio trocando o ID na URL).
 */
class ExpenseService
{
    /**
     * Lista as despesas do usuário aplicando filtros, ordenação e paginação.
     *
     * @return array{items: Expense[], pagination: array}
     * @throws ValidationException se os parâmetros de busca forem inválidos.
     */
    public function list(User $user, ExpenseSearch $search): array
    {
        if (!$search->validate()) {
            throw new ValidationException($search->getErrors());
        }

        $dataProvider = $search->buildDataProvider($user->getId());
        $pagination = $dataProvider->getPagination();

        return [
            'items' => $dataProvider->getModels(),
            'pagination' => [
                'page' => $pagination->getPage() + 1, // interno começa em 0
                'per_page' => $pagination->getPageSize(),
                'total' => (int) $dataProvider->getTotalCount(),
                'page_count' => $pagination->getPageCount(),
            ],
        ];
    }

    /**
     * Cria uma despesa para o usuário.
     *
     * O `user_id` é definido pelo SERVIDOR a partir do usuário autenticado, nunca
     * pelo cliente — e `setAttributes()` só atribui campos "safe" das rules, então
     * mesmo que o cliente envie "user_id" no corpo, ele é ignorado.
     *
     * @param array $data corpo da requisição (description, category, amount, expense_date)
     * @throws ValidationException se os dados forem inválidos.
     */
    public function create(User $user, array $data): Expense
    {
        $expense = new Expense();
        $expense->user_id = $user->getId();
        $expense->setAttributes($data);

        if (!$expense->save()) {
            throw new ValidationException($expense->getErrors());
        }

        return $expense;
    }

    /**
     * Atualiza uma despesa existente do usuário (edição de qualquer campo).
     *
     * @throws NotFoundHttpException se a despesa não existir ou não pertencer ao usuário.
     * @throws ValidationException  se os dados forem inválidos.
     */
    public function update(User $user, int $id, array $data): Expense
    {
        $expense = $this->findOwned($user, $id);
        $expense->setAttributes($data);

        if (!$expense->save()) {
            throw new ValidationException($expense->getErrors());
        }

        return $expense;
    }

    /**
     * Exclui uma despesa do usuário pelo ID.
     *
     * @throws NotFoundHttpException se a despesa não existir ou não pertencer ao usuário.
     */
    public function delete(User $user, int $id): void
    {
        $expense = $this->findOwned($user, $id);
        $expense->delete();
    }

    /**
     * Detalha uma despesa específica do usuário.
     *
     * @throws NotFoundHttpException se a despesa não existir ou não pertencer ao usuário.
     */
    public function view(User $user, int $id): Expense
    {
        return $this->findOwned($user, $id);
    }

    /**
     * Busca a despesa GARANTINDO a posse (user_id na cláusula WHERE).
     *
     * Decisão de design: se a despesa existe mas é de outro usuário, retornamos
     * 404 (e não 403). Assim não revelamos a existência de recursos alheios —
     * o cliente não consegue distinguir "não existe" de "não é seu".
     */
    private function findOwned(User $user, int $id): Expense
    {
        $expense = Expense::findOne(['id' => $id, 'user_id' => $user->getId()]);

        if ($expense === null) {
            throw new NotFoundHttpException('Despesa não encontrada.');
        }

        return $expense;
    }
}
