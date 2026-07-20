<?php

/**
 * Configuração da aplicação de CONSOLE (usada para migrations e afins).
 */

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

return [
    'id' => 'despesas-console',
    'basePath' => dirname(__DIR__),
    'language' => 'pt-BR',
    'controllerNamespace' => 'app\commands',
    'container' => require __DIR__ . '/container.php',
    'components' => [
        'db' => $db,
    ],
    'params' => $params,
];
