<?php

// Bootstrap dos testes. O módulo Yii2 do Codeception cuida de inicializar a
// aplicação a partir de config/test.php; aqui basta o autoload do Composer.

defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
