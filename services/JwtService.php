<?php

namespace app\services;

use app\models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Serviço responsável por emitir e validar tokens JWT.
 *
 * Encapsula toda a interação com a biblioteca firebase/php-jwt. Nenhuma outra
 * parte do sistema precisa conhecer os detalhes de assinatura/decodificação —
 * isso é o princípio da responsabilidade única (o "S" de SOLID).
 *
 * OBS.: O Yii2 NÃO possui suporte nativo a JWT. Por isso trazemos uma biblioteca
 * dedicada e a isolamos aqui.
 */
class JwtService
{
    public function __construct(
        private string $secret,
        private string $issuer,
        private int $ttl
    ) {
    }

    /**
     * Emite um token para o usuário informado.
     *
     * @return array{token: string, expires_in: int}
     */
    public function issueToken(User $user): array
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,   // emissor
            'iat' => $now,            // emitido em
            'exp' => $now + $this->ttl, // expira em
            'uid' => $user->getId(),  // "claim" própria: id do usuário
        ];

        return [
            'token' => JWT::encode($payload, $this->secret, 'HS256'),
            'expires_in' => $this->ttl,
        ];
    }

    /**
     * Valida o token e devolve o usuário correspondente, ou null se o token for
     * inválido/expirado ou o usuário não existir mais.
     *
     * A biblioteca já valida assinatura e expiração (exp) e lança exceção em caso
     * de falha — por isso capturamos qualquer Throwable e tratamos como "inválido".
     */
    public function parseToken(string $token): ?User
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Throwable $e) {
            return null;
        }

        if (!isset($decoded->uid)) {
            return null;
        }

        return User::findOne((int) $decoded->uid);
    }
}
