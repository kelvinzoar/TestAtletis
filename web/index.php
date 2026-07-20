<?php

/**
 * Ponto de entrada HTTP da aplicação (front controller).
 * Toda requisição web passa por aqui: cria a aplicação e roda o ciclo requisição→resposta.
 */

// YII_DEBUG=true mostra stack traces detalhados; em produção deve ser false.
defined('YII_DEBUG') or define('YII_DEBUG', getenv('YII_DEBUG') === 'true');
// YII_ENV controla qual configuração/ambiente é carregado (dev, test, prod).
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

// Cria a aplicação web e executa o ciclo requisição→resposta.
(new yii\web\Application($config))->run();
