<?php

use yii\db\Migration;

/**
 * Cria a tabela `expense` e sua chave estrangeira para `user`.
 */
class m240101_000002_create_expense_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%expense}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'description' => $this->string(255)->notNull(),
            // Categoria como ENUM no banco: mais uma barreira de integridade,
            // além da validação no model. Mantém sincronizado com Expense::categories().
            'category' => "ENUM('alimentacao','transporte','lazer') NOT NULL",
            // DINHEIRO como DECIMAL(10,2), NUNCA float: evita erros de arredondamento.
            // (10,2) => até 99.999.999,99.
            'amount' => $this->decimal(10, 2)->notNull(),
            'expense_date' => $this->date()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Índice na FK acelera as consultas por usuário (que são a maioria aqui).
        $this->createIndex('idx-expense-user_id', '{{%expense}}', 'user_id');

        // Índice composto para a consulta típica: despesas de um usuário por data.
        $this->createIndex('idx-expense-user_date', '{{%expense}}', ['user_id', 'expense_date']);

        // FK com ON DELETE CASCADE: ao remover um usuário, suas despesas somem junto.
        $this->addForeignKey(
            'fk-expense-user_id',
            '{{%expense}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-expense-user_id', '{{%expense}}');
        $this->dropTable('{{%expense}}');
    }
}
