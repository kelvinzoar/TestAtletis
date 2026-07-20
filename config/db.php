<?php

/**
 * Configuração da conexão com o banco (yii\db\Connection).
 * Os dados vêm de variáveis de ambiente (12-factor app), nunca hardcoded.
 * Analogia C#: é a "ConnectionString" do appsettings, montada a partir do ambiente.
 */
return [
    'class' => yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_NAME') ?: 'despesas'
    ),
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
