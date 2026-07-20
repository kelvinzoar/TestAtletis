<?php

/**
 * Configuração do container de injeção de dependências (DI) do Yii.
 *
 * Registramos os serviços da camada de negócio como SINGLETONS. Isso permite
 * que controllers e componentes recebam os serviços por injeção no construtor,
 * favorecendo os princípios SOLID (em especial o D — inversão de dependência)
 * e facilitando testes (é possível trocar por mocks).
 *
 * Analogia C#: equivale ao `builder.Services.AddSingleton<IExpenseService, ExpenseService>()`
 * do ASP.NET Core.
 */

use app\services\AuthService;
use app\services\ExpenseService;
use app\services\JwtService;

return [
    'singletons' => [
        // O JwtService depende de configuração (segredo/issuer/ttl). Usamos uma
        // closure para lê-la de Yii::$app->params no momento em que o serviço é
        // resolvido (lazy), quando a aplicação já está totalmente inicializada.
        JwtService::class => function () {
            $cfg = Yii::$app->params['jwt'];
            return new JwtService($cfg['secret'], $cfg['issuer'], $cfg['ttl']);
        },

        // AuthService e ExpenseService não têm configuração; suas dependências
        // (ex.: AuthService precisa de JwtService) são resolvidas automaticamente
        // pelo container via reflexão do construtor (autowiring).
        AuthService::class => AuthService::class,
        ExpenseService::class => ExpenseService::class,
    ],
];
