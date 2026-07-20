<?php

namespace app\models\forms;

use app\models\User;
use yii\base\Model;

/**
 * Form (DTO de entrada) para registro de usuário.
 *
 * Por que uma classe separada do model User? Para separar a "forma dos dados que
 * chegam pela API" da "entidade persistida". Aqui validamos a confirmação de
 * senha e a unicidade do e-mail antes de criar o usuário.
 */
class RegisterForm extends Model
{
    // Propriedades SEM tipo de propósito: o load() do Yii atribui valores crus da
    // requisição (que podem vir como string, array, etc.). Deixar sem tipagem evita
    // TypeError (erro 500) e transfere a checagem para os validators (erro 422).
    public $email;
    public $password;
    public $password_confirm;

    public function rules(): array
    {
        return [
            [['email', 'password', 'password_confirm'], 'required'],
            ['email', 'trim'],
            ['email', 'string', 'max' => 255],
            ['email', 'email'],
            // Unicidade verificada contra a tabela do model User (targetClass).
            ['email', 'unique', 'targetClass' => User::class,
                'message' => 'Este e-mail já está cadastrado.'],
            ['password', 'string', 'min' => 6],
            // Confirmação precisa bater com a senha.
            ['password_confirm', 'compare', 'compareAttribute' => 'password',
                'message' => 'A confirmação de senha não confere.'],
        ];
    }
}
