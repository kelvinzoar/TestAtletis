<?php

use yii\db\Migration;

/**
 * Cria a tabela `user`.
 *
 * Migrations versionam o schema do banco (equivalente às EF Migrations do C#).
 * Usamos safeUp/safeDown: rodam dentro de uma transação, então em caso de erro
 * o banco não fica num estado parcial.
 */
class m240101_000001_create_user_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string(255)->notNull()->unique(),
            // bcrypt gera hashes de ~60 chars; 255 dá folga para outros algoritmos.
            'password_hash' => $this->string(255)->notNull(),
            // Timestamps Unix preenchidos pelo TimestampBehavior do model.
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%user}}');
    }
}
