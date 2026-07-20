<?php

namespace app\exceptions;

use yii\web\HttpException;

/**
 * Exceção de validação lançada pela camada de serviço quando os dados são
 * inválidos. Mapeada para HTTP 422 (Unprocessable Entity).
 *
 * Carrega os erros campo->mensagens (formato de $model->getErrors()) para que o
 * ApiErrorHandler os inclua no corpo da resposta JSON.
 *
 * Por que uma exceção própria? Para que a camada de serviço possa sinalizar
 * "dados inválidos" sem conhecer detalhes de HTTP, mantendo a separação de
 * responsabilidades. O tratamento HTTP fica centralizado no ApiErrorHandler.
 */
class ValidationException extends HttpException
{
    /** @var array<string, string[]> erros no formato campo => [mensagens] */
    public array $errors;

    public function __construct(array $errors, string $message = 'Os dados enviados são inválidos.')
    {
        $this->errors = $errors;
        parent::__construct(422, $message);
    }

    public function getName(): string
    {
        return 'Unprocessable Entity';
    }
}
