<?php

namespace app\models\forms;

use yii\base\Model;

/**
 * Form (DTO de entrada) para login.
 * Só valida presença/formato; a conferência de credenciais é feita no AuthService.
 */
class LoginForm extends Model
{
    // Sem tipo: o load() atribui valores crus; validators cuidam do resto.
    public $email;
    public $password;

    public function rules(): array
    {
        return [
            [['email', 'password'], 'required'],
            ['email', 'email'],
        ];
    }
}
