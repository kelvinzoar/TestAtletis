<?php

/**
 * Configuração da aplicação WEB (API).
 * Aqui montamos os componentes da aplicação (request, response, banco, roteamento, DI).
 */

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'despesas-api',
    'name' => 'API de Despesas Pessoais',
    'basePath' => dirname(__DIR__),
    'language' => 'pt-BR',

    // Registra os serviços de negócio no container de DI.
    'container' => require __DIR__ . '/container.php',

    'components' => [
        'request' => [
            // API stateless: sem cookies e sem CSRF (proteção pensada para formulários web).
            'enableCookieValidation' => false,
            'enableCsrfValidation' => false,
            // Faz o parse automático de corpo JSON -> $request->getBodyParams().
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
            ],
        ],

        // Toda resposta é serializada como JSON por padrão.
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],

        // Componente de autenticação. Stateless: sem sessão, sem tela de login.
        // A identidade é resolvida a cada request pelo componente JwtAuth.
        'user' => [
            'identityClass' => app\models\User::class,
            'enableSession' => false,
            'loginUrl' => null,
        ],

        // Handler de erros customizado: converte exceções em JSON e inclui os
        // erros de validação de campo quando aplicável (ver ApiErrorHandler).
        'errorHandler' => [
            'class' => app\components\ApiErrorHandler::class,
        ],

        'db' => $db,

        // Roteamento REST: mapeia método + caminho HTTP para as actions dos controllers.
        // enableStrictParsing => rotas não declaradas retornam 404 (nada de rotas "mágicas").
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                'POST auth/register' => 'auth/register',
                'POST auth/login' => 'auth/login',

                'GET expenses' => 'expense/index',
                'POST expenses' => 'expense/create',
                'GET expenses/<id:\d+>' => 'expense/view',
                'PUT,PATCH expenses/<id:\d+>' => 'expense/update',
                'DELETE expenses/<id:\d+>' => 'expense/delete',
            ],
        ],
    ],

    'params' => $params,
];

return $config;
