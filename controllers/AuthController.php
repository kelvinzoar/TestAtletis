<?php

namespace app\controllers;

use app\exceptions\ValidationException;
use app\models\forms\LoginForm;
use app\models\forms\RegisterForm;
use app\services\AuthService;
use Yii;

/**
 * Endpoints de autenticação: registro e login.
 * Ambos são públicos (sem token) — declarados em authExcept().
 *
 * Os serviços são injetados pelo container de DI no construtor.
 */
class AuthController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        private AuthService $authService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    protected function authExcept(): array
    {
        return ['register', 'login'];
    }

    /**
     * POST /auth/register
     * Cria um usuário e já devolve um token para uso imediato.
     */
    public function actionRegister(): array
    {
        $form = new RegisterForm();
        // '' como segundo argumento: carrega os dados do nível raiz do JSON
        // (sem exigir um "envelope" tipo {"RegisterForm": {...}}).
        $form->load(Yii::$app->request->getBodyParams(), '');

        if (!$form->validate()) {
            throw new ValidationException($form->getErrors());
        }

        $user = $this->authService->register($form);

        // 201 Created é a resposta semântica para criação de recurso.
        Yii::$app->response->setStatusCode(201);

        return ['user' => $user];
    }

    /**
     * POST /auth/login
     * Valida credenciais e devolve o token JWT.
     */
    public function actionLogin(): array
    {
        $form = new LoginForm();
        $form->load(Yii::$app->request->getBodyParams(), '');

        if (!$form->validate()) {
            throw new ValidationException($form->getErrors());
        }

        return $this->authService->login($form);
    }
}
