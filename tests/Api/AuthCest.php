<?php

namespace Tests\Api;

use Tests\ApiTester;

/**
 * Testes dos endpoints de autenticação.
 * Cada método público é um caso de teste (convenção "Cest" do Codeception).
 */
class AuthCest
{
    public function _before(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function registraELogaComSucesso(ApiTester $I): void
    {
        $I->sendPost('/auth/register', [
            'email' => 'joao@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['user' => ['email' => 'joao@example.com']]);
        // Garante que o hash da senha NUNCA é exposto na resposta.
        $I->dontSeeResponseContainsJson(['user' => ['password_hash' => true]]);

        $I->sendPost('/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'secret123',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.token');
    }

    public function naoPermiteEmailDuplicado(ApiTester $I): void
    {
        $payload = [
            'email' => 'dup@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
        ];
        $I->sendPost('/auth/register', $payload);
        $I->seeResponseCodeIs(201);

        $I->sendPost('/auth/register', $payload);
        $I->seeResponseCodeIs(422); // erro de validação (e-mail já cadastrado)
    }

    public function loginComSenhaErradaFalha(ApiTester $I): void
    {
        $I->sendPost('/auth/register', [
            'email' => 'maria@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
        ]);

        $I->sendPost('/auth/login', [
            'email' => 'maria@example.com',
            'password' => 'senha-errada',
        ]);
        $I->seeResponseCodeIs(401);
    }
}
