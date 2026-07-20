<?php

// O namespace de suporte do Codeception (definido por `namespace: Tests` no
// codeception.yml) é "Tests" — por isso o Actor e o trait gerado ficam sob ele.
namespace Tests;

/**
 * "Actor" da suíte de API. Os métodos ($I->sendPOST, $I->seeResponseCodeIs, ...)
 * são gerados pelo Codeception a partir dos módulos habilitados quando você roda
 * `vendor/bin/codecept build` (ficam em tests/Support/_generated).
 */
class ApiTester extends \Codeception\Actor
{
    use _generated\ApiTesterActions;
}
