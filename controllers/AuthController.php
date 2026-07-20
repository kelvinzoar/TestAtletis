<?php

namespace app\controllers;

use app\exceptions\ValidationException;
use app\models\forms\LoginForm;
use app\models\forms\RegisterForm;
use app\services\AuthService;
use OpenApi\Attributes as OA;
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
    #[OA\Post(
        path: '/auth/register',
        tags: ['Auth'],
        summary: 'Registra um novo usuário',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'password_confirm'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'teste@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6, example: 'secret123'),
                    new OA\Property(property: 'password_confirm', type: 'string', format: 'password', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuário criado (já autenticado, com token)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                    new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function actionRegister(): array
    {
        $form = new RegisterForm();
        // '' como segundo argumento: carrega os dados do nível raiz do JSON
        // (sem exigir um "envelope" tipo {"RegisterForm": {...}}).
        $form->load(Yii::$app->request->getBodyParams(), '');

        if (!$form->validate()) {
            throw new ValidationException($form->getErrors());
        }

        // register() já cria o usuário e emite o token (auto-login).
        $result = $this->authService->register($form);

        // 201 Created é a resposta semântica para criação de recurso.
        Yii::$app->response->setStatusCode(201);

        return $result;
    }

    /**
     * POST /auth/login
     * Valida credenciais e devolve o token JWT.
     */
    #[OA\Post(
        path: '/auth/login',
        tags: ['Auth'],
        summary: 'Autentica o usuário e retorna um token JWT',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'teste@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Autenticado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                    new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciais inválidas',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
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
