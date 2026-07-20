<?php

namespace app\components;

use app\services\JwtService;
use yii\filters\auth\AuthMethod;
use yii\web\Request;
use yii\web\Response;
use yii\web\User;

/**
 * Método de autenticação por JWT no header "Authorization: Bearer <token>".
 *
 * É um filtro de action (behavior) aplicado nos controllers protegidos.
 * Analogia C#: um middleware/AuthenticationHandler que lê o Bearer token e
 * popula o usuário atual (o atributo [Authorize] passa a valer).
 *
 * Recebe o JwtService por injeção no construtor (autowiring do container de DI).
 */
class JwtAuth extends AuthMethod
{
    public function __construct(private JwtService $jwtService, array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * Tenta autenticar a requisição. Retornar null sinaliza "não autenticado",
     * e a classe base (AuthMethod) dispara HTTP 401 automaticamente quando a
     * autenticação é obrigatória para a action.
     *
     * @param User     $user
     * @param Request  $request
     * @param Response $response
     */
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');

        if ($authHeader !== null && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $identity = $this->jwtService->parseToken($matches[1]);

            if ($identity !== null) {
                // Define a identidade sem sessão (API stateless).
                $user->switchIdentity($identity);
                return $identity;
            }
        }

        return null;
    }
}
