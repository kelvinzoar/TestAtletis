<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Model da despesa (tabela `expense`).
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $description
 * @property string $category
 * @property string $amount        Guardado como DECIMAL(10,2); o PDO devolve string.
 * @property string $expense_date  Formato Y-m-d.
 * @property int    $created_at
 * @property int    $updated_at
 */
class Expense extends ActiveRecord
{
    // Categorias permitidas expostas como constantes: evita "strings mágicas"
    // espalhadas pelo código (mesma ideia de um enum em C#).
    public const CATEGORY_FOOD = 'alimentacao';
    public const CATEGORY_TRANSPORT = 'transporte';
    public const CATEGORY_LEISURE = 'lazer';

    public static function categories(): array
    {
        return [
            self::CATEGORY_FOOD,
            self::CATEGORY_TRANSPORT,
            self::CATEGORY_LEISURE,
        ];
    }

    public static function tableName(): string
    {
        return '{{%expense}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * Validações do model — garantem a integridade antes de tocar o banco.
     *
     * OBSERVAÇÃO DE SEGURANÇA (mass assignment): note que `user_id` NÃO está nas
     * regras. No Yii, apenas atributos listados nas rules são "safe" para
     * atribuição em massa (via load()/setAttributes()). Assim, o cliente não
     * consegue forjar o dono da despesa pelo corpo da requisição — o user_id é
     * definido apenas pelo servidor, a partir do usuário autenticado.
     * (Em C# seria o equivalente a proteger contra over-posting com um DTO.)
     */
    public function rules(): array
    {
        return [
            [['description', 'category', 'amount', 'expense_date'], 'required'],
            ['description', 'string', 'max' => 255],
            // Categoria restrita ao conjunto permitido.
            ['category', 'in', 'range' => self::categories(),
                'message' => 'Categoria inválida. Use: ' . implode(', ', self::categories()) . '.'],
            // Valor decimal positivo (dinheiro nunca é float aqui — ver migration).
            ['amount', 'number', 'min' => 0.01],
            // Data no formato ISO (Y-m-d).
            ['expense_date', 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Campos expostos no JSON. O `amount` é convertido para float (o PDO devolve
     * a coluna DECIMAL como string); os demais campos saem como estão — a data já
     * vem no formato ISO (Y-m-d). Isso deixa a resposta previsível para o cliente.
     */
    public function fields(): array
    {
        return [
            'id',
            'description',
            'category',
            'amount' => fn(self $m) => (float) $m->amount,
            'expense_date',
            'created_at',
            'updated_at',
        ];
    }
}
