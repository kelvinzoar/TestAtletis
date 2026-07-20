<?php

namespace Tests\Api;

use Tests\ApiTester;

/**
 * Testes do CRUD de despesas, incluindo o ponto mais crítico do desafio:
 * um usuário NÃO pode acessar as despesas de outro (isolamento de dados).
 */
class ExpenseCest
{
    public function _before(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function exigeAutenticacao(ApiTester $I): void
    {
        // Sem header Authorization -> 401.
        $I->sendGet('/expenses');
        $I->seeResponseCodeIs(401);
    }

    public function criaEListaDespesa(ApiTester $I): void
    {
        $token = $this->novoUsuarioComToken($I, 'carlos@example.com');
        $I->amBearerAuthenticated($token);

        $I->sendPost('/expenses', [
            'description' => 'Almoço',
            'category' => 'alimentacao',
            'amount' => 42.90,
            'expense_date' => '2026-07-10',
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['expense' => ['description' => 'Almoço']]);

        $I->sendGet('/expenses');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['items' => [['description' => 'Almoço']]]);
    }

    public function categoriaInvalidaRetorna422(ApiTester $I): void
    {
        $token = $this->novoUsuarioComToken($I, 'ana@example.com');
        $I->amBearerAuthenticated($token);

        $I->sendPost('/expenses', [
            'description' => 'Curso',
            'category' => 'educacao', // não permitida
            'amount' => 100,
            'expense_date' => '2026-07-10',
        ]);
        $I->seeResponseCodeIs(422);
    }

    public function naoAcessaDespesaDeOutroUsuario(ApiTester $I): void
    {
        // Usuário A cria uma despesa.
        $tokenA = $this->novoUsuarioComToken($I, 'usuarioA@example.com');
        $I->amBearerAuthenticated($tokenA);
        $I->sendPost('/expenses', [
            'description' => 'Uber',
            'category' => 'transporte',
            'amount' => 25.00,
            'expense_date' => '2026-07-11',
        ]);
        $idDaDespesaDeA = $I->grabDataFromResponseByJsonPath('$.expense.id')[0];

        // Usuário B tenta acessar a despesa de A -> deve receber 404.
        $tokenB = $this->novoUsuarioComToken($I, 'usuarioB@example.com');
        $I->amBearerAuthenticated($tokenB);
        $I->sendGet("/expenses/{$idDaDespesaDeA}");
        $I->seeResponseCodeIs(404);
    }

    /**
     * Helper: registra um usuário e devolve um token JWT válido.
     */
    private function novoUsuarioComToken(ApiTester $I, string $email): string
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/auth/register', [
            'email' => $email,
            'password' => 'secret123',
            'password_confirm' => 'secret123',
        ]);
        $I->sendPost('/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        return $I->grabDataFromResponseByJsonPath('$.token')[0];
    }
}
