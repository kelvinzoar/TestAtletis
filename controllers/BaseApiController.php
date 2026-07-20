<?php

namespace app\controllers;

use app\components\JwtAuth;
use app\models\User;
use Yii;
use yii\rest\Controller;
use yii\web\Response;

/**
 * Controller base da API.
 *
 * Herda de yii\rest\Controller (que já traz negociação de conteúdo, filtro de
 * verbos HTTP e serialização). Aqui adicionamos a autenticação JWT como behavior
 * padrão para todas as actions.
 */
class BaseApiController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Autenticação JWT em todas as actions, exceto as listadas em authExcept().
        $behaviors['authenticator'] = [
            'class' => JwtAuth::class,
            'except' => $this->authExcept(),
        ];

        // Garante negociação apenas para JSON.
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => Response::FORMAT_JSON,
        ];

        return $behaviors;
    }

    /**
     * Actions que NÃO exigem autenticação (ex.: login e registro).
     * Sobrescreva nos controllers filhos conforme necessário.
     *
     * @return string[]
     */
    protected function authExcept(): array
    {
        return [];
    }

    /**
     * Retorna o usuário autenticado (populado pelo JwtAuth).
     */
    protected function currentUser(): User
    {
        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        return $identity;
    }
}
