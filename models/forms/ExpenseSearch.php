<?php

namespace app\models\forms;

use app\models\Expense;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Form de busca/filtro da listagem de despesas.
 *
 * Concentra em um só lugar as regras de: filtro por categoria e por período
 * (mês/ano), ordenação por data e paginação. Isso mantém o controller magro e
 * as regras de consulta testáveis.
 * Analogia C#: um objeto de query/filtro que você passaria a um repositório.
 */
class ExpenseSearch extends Model
{
    // Propriedades SEM tipo: os query params chegam como string via load(). Deixar
    // sem tipagem evita TypeError e deixa a validação/casts explícitos assumirem.
    public $category;
    public $month;              // 1..12
    public $year;
    public $sort = 'desc';      // ordenação por expense_date: asc|desc
    public $page = 1;
    public $per_page = 15;

    public function rules(): array
    {
        return [
            ['category', 'in', 'range' => Expense::categories(),
                'message' => 'Categoria inválida.'],
            ['month', 'integer', 'min' => 1, 'max' => 12],
            ['year', 'integer', 'min' => 2000, 'max' => 2100],
            ['sort', 'in', 'range' => ['asc', 'desc']],
            ['page', 'integer', 'min' => 1],
            ['per_page', 'integer', 'min' => 1, 'max' => 100],
            // Filtrar por mês exige informar o ano (senão o período é ambíguo).
            ['month', 'validateMonthNeedsYear'],
        ];
    }

    public function validateMonthNeedsYear(string $attribute): void
    {
        if (!empty($this->month) && empty($this->year)) {
            $this->addError('year', 'Informe o ano ao filtrar por mês.');
        }
    }

    /**
     * Monta a consulta já restrita ao usuário dono e devolve um ActiveDataProvider
     * (que cuida de paginação e contagem total).
     *
     * O filtro `user_id` é aplicado SEMPRE — é a garantia de que cada usuário só
     * enxerga as próprias despesas.
     */
    public function buildDataProvider(int $userId): ActiveDataProvider
    {
        $query = Expense::find()->andWhere(['user_id' => $userId]);

        if (!empty($this->category)) {
            $query->andWhere(['category' => $this->category]);
        }

        // Filtro por período usando intervalo de datas (amigável a índices,
        // diferente de aplicar funções MONTH()/YEAR() sobre a coluna).
        if (!empty($this->year)) {
            if (!empty($this->month)) {
                $start = sprintf('%04d-%02d-01', $this->year, $this->month);
                $end = date('Y-m-t', strtotime($start)); // último dia do mês
            } else {
                $start = sprintf('%04d-01-01', $this->year);
                $end = sprintf('%04d-12-31', $this->year);
            }
            $query->andWhere(['between', 'expense_date', $start, $end]);
        }

        $direction = $this->sort === 'asc' ? SORT_ASC : SORT_DESC;

        return new ActiveDataProvider([
            'query' => $query->orderBy(['expense_date' => $direction, 'id' => SORT_DESC]),
            'pagination' => [
                'pageSize' => (int) $this->per_page,
                // O Yii pagina a partir de 0; a API expõe a partir de 1.
                'page' => (int) $this->page - 1,
            ],
        ]);
    }
}
