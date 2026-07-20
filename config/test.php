<?php

/**
 * Configuração usada pelos testes automatizados.
 * Reaproveita a config web e apenas aponta para um BANCO DE TESTE separado,
 * para nunca tocar os dados de desenvolvimento.
 */
$config = require __DIR__ . '/web.php';

$config['components']['db']['dsn'] = sprintf(
    'mysql:host=%s;port=%s;dbname=%s',
    getenv('DB_HOST') ?: '127.0.0.1',
    getenv('DB_PORT') ?: '3306',
    getenv('DB_TEST_NAME') ?: 'despesas_test'
);

return $config;
