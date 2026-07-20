<?php

namespace app\components;

use app\exceptions\ValidationException;
use yii\web\ErrorHandler;

/**
 * Handler de erros da API.
 *
 * Centraliza a conversão de exceções em respostas JSON consistentes. Em especial,
 * quando a exceção é uma ValidationException, inclui o dicionário de erros por
 * campo no corpo da resposta (HTTP 422).
 *
 * Manter isso num único ponto evita repetir tratamento de erro em cada controller
 * (DRY) e mantém o formato de erro padronizado para quem consome a API.
 */
class ApiErrorHandler extends ErrorHandler
{
    /**
     * @param \Throwable $exception
     */
    protected function convertExceptionToArray($exception): array
    {
        $array = parent::convertExceptionToArray($exception);

        if ($exception instanceof ValidationException) {
            // Ex.: { "errors": { "email": ["Este e-mail já está cadastrado."] } }
            $array['errors'] = $exception->errors;
        }

        return $array;
    }
}
