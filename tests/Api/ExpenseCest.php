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

    public function detalhaDespesaPorId(ApiTester $I): void
    {
        $token = $this->novoUsuarioComToken($I, 'detalhe@example.com');
        $I->amBearerAuthenticated($token);

        $I->sendPost('/expenses', [
            'description' => 'Livro',
            'category' => 'lazer',
            'amount' => 59.90,
            'expense_date' => '2026-07-12',
        ]);
        $id = $I->grabDataFromResponseByJsonPath('$.expense.id')[0];

        $I->sendGet("/expenses/{$id}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['expense' => ['id' => $id, 'description' => 'Livro']]);
    }

    public function editaDespesa(ApiTester $I): void
    {
        $token = $this->novoUsuarioComToken($I, 'edita@example.com');
        $I->amBearerAuthenticated($token);

        $I->sendPost('/expenses', [
            'description' => 'Padaria',
            'category' => 'alimentacao',
            'amount' => 10.00,
            'expense_date' => '2026-07-13',
        ]);
        $id = $I->grabDataFromResponseByJsonPath('$.expense.id')[0];

        // Edita qualquer campo (PUT).
        $I->sendPut("/expenses/{$id}", [
            'description' => 'Padaria (corrigido)',
            'category' => 'alimentacao',
            'amount' => 15.50,
            'expense_date' => '2026-07-13',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['expense' => ['description' => 'Padaria (corrigido)', 'amount' => 15.5]]);

        // Confirma que a alteração persistiu.
        $I->sendGet("/expenses/{$id}");
        $I->seeResponseContainsJson(['expense' => ['description' => 'Padaria (corrigido)']]);
    }

    public function excluiDespesa(ApiTester $I): void
    {
        $token = $this->novoUsuarioComToken($I, 'exclui@example.com');
        $I->amBearerAuthenticated($token);

        $I->sendPost('/expenses', [
            'description' => 'Cinema',
            'category' => 'lazer',
            'amount' => 30.00,
            'expense_date' => '2026-07-14',
        ]);
        $id = $I->grabDataFromResponseByJsonPath('$.expense.id')[0];

        $I->sendDelete("/expenses/{$id}");
        $I->seeResponseCodeIs(204);

        // Após excluir, consultar a mesma despesa deve dar 404.
        $I->sendGet("/expenses/{$id}");
        $I->seeResponseCodeIs(404);
    }

    public function naoEditaNemExcluiDespesaDeOutroUsuario(ApiTester $I): void
    {
        // Usuário A cria uma despesa.
        $tokenA = $this->novoUsuarioComToken($I, 'donoA@example.com');
        $I->amBearerAuthenticated($tokenA);
        $I->sendPost('/expenses', [
            'description' => 'Gasolina',
            'category' => 'transporte',
            'amount' => 200.00,
            'expense_date' => '2026-07-15',
        ]);
        $id = $I->grabDataFromResponseByJsonPath('$.expense.id')[0];

        // Usuário B não pode editar nem excluir a despesa de A -> 404 nos dois.
        $tokenB = $this->novoUsuarioComToken($I, 'donoB@example.com');
        $I->amBearerAuthenticated($tokenB);

        $I->sendPut("/expenses/{$id}", [
            'description' => 'Invadido',
            'category' => 'lazer',
            'amount' => 1.00,
            'expense_date' => '2026-07-15',
        ]);
        $I->seeResponseCodeIs(404);

        $I->sendDelete("/expenses/{$id}");
        $I->seeResponseCodeIs(404);

        // Garante que a despesa de A continua intacta.
        $I->amBearerAuthenticated($tokenA);
        $I->sendGet("/expenses/{$id}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['expense' => ['description' => 'Gasolina']]);
    }

    public function filtraPorCategoriaEPeriodo(ApiTester $I): void
    {
        $token = $this->novoUsuarioComToken($I, 'filtro@example.com');
        $I->amBearerAuthenticated($token);

        // Duas despesas em meses/categorias diferentes.
        $I->sendPost('/expenses', [
            'description' => 'Mercado julho',
            'category' => 'alimentacao',
            'amount' => 80.00,
            'expense_date' => '2026-07-05',
        ]);
        $I->sendPost('/expenses', [
            'description' => 'Onibus junho',
            'category' => 'transporte',
            'amount' => 20.00,
            'expense_date' => '2026-06-05',
        ]);

        // Filtro por categoria -> só a de transporte (total 1).
        $I->sendGet('/expenses?category=transporte');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['pagination' => ['total' => 1]]);
        $I->seeResponseContainsJson(['items' => [['description' => 'Onibus junho']]]);

        // Filtro por período (junho/2026) -> só a de junho (total 1).
        $I->sendGet('/expenses?year=2026&month=6');
        $I->seeResponseContainsJson(['pagination' => ['total' => 1]]);
        $I->seeResponseContainsJson(['items' => [['description' => 'Onibus junho']]]);

        // Filtrar por mês sem informar o ano -> 422.
        $I->sendGet('/expenses?month=6');
        $I->seeResponseCodeIs(422);
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
