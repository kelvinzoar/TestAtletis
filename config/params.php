<?php

/**
 * Parâmetros da aplicação (acessíveis via Yii::$app->params[...]).
 * Aqui centralizamos a configuração do JWT.
 */
return [
    'jwt' => [
        // Segredo usado para assinar/validar o token (algoritmo HS256).
        // Fallback com >= 32 bytes: o firebase/php-jwt 7.x exige chave mínima para HS256.
        'secret' => getenv('JWT_SECRET') ?: 'insecure-dev-secret-change-me-0123456789abcdef',
        // "issuer": quem emitiu o token (validado ao decodificar).
        'issuer' => getenv('JWT_ISSUER') ?: 'despesas-api',
        // Tempo de vida do token, em segundos (padrão: 1 hora).
        'ttl' => (int) (getenv('JWT_TTL') ?: 3600),
    ],
];
