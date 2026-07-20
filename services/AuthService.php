<?php

namespace app\services;

use app\exceptions\ValidationException;
use app\models\forms\LoginForm;
use app\models\forms\RegisterForm;
use app\models\User;
use yii\web\UnauthorizedHttpException;

/**
 * Serviço de autenticação: registro e login.
 *
 * Recebe o JwtService por injeção (resolvido pelo container de DI). Isso desacopla
 * a autenticação da implementação concreta de tokens — se um dia trocarmos JWT
 * por outra estratégia, este serviço muda pouco.
 */
class AuthService
{
    public function __construct(private JwtService $jwt)
    {
    }

    /**
     * Cria um novo usuário a partir de um RegisterForm JÁ VALIDADO.
     *
     * @throws ValidationException se o model não passar nas suas próprias regras.
     */
    public function register(RegisterForm $form): User
    {
        $user = new User();
        $user->email = $form->email;
        $user->setPassword($form->password);

        if (!$user->save()) {
            // Rede de segurança: em teoria o form já validou, mas o model é a
            // última barreira de integridade antes do banco.
            throw new ValidationException($user->getErrors());
        }

        return $user;
    }

    /**
     * Autentica o usuário e devolve o token + dados do usuário.
     *
     * @return array{token: string, expires_in: int, user: User}
     * @throws UnauthorizedHttpException se as credenciais forem inválidas.
     */
    public function login(LoginForm $form): array
    {
        $user = User::findByEmail($form->email);

        // Mensagem genérica de propósito: não revelamos se o e-mail existe ou se
        // apenas a senha está errada (evita enumeração de usuários).
        if ($user === null || !$user->validatePassword($form->password)) {
            throw new UnauthorizedHttpException('E-mail ou senha inválidos.');
        }

        return $this->jwt->issueToken($user) + ['user' => $user];
    }
}
