<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Model do usuário (tabela `user`).
 *
 * Herda de ActiveRecord (padrão Active Record): o objeto é, ao mesmo tempo, a
 * entidade e o acesso a dados. Analogia C#: como se a entidade do EF Core e o
 * DbSet estivessem fundidos na mesma classe.
 *
 * Implementa IdentityInterface para que o Yii saiba como representar o usuário
 * autenticado (Yii::$app->user->identity).
 *
 * @property int    $id
 * @property string $email
 * @property string $password_hash
 * @property int    $created_at
 * @property int    $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        // {{%user}} aplica o prefixo de tabela configurado (boa prática do Yii).
        return '{{%user}}';
    }

    /**
     * Preenche created_at/updated_at automaticamente (timestamps Unix).
     * Analogia C#: interceptors de SaveChanges no EF Core.
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * Regras de validação — a integridade dos dados é garantida no model.
     * Analogia C#: Data Annotations ([Required], [EmailAddress]) centralizadas.
     */
    public function rules(): array
    {
        return [
            [['email', 'password_hash'], 'required'],
            ['email', 'string', 'max' => 255],
            ['email', 'email'],
            ['email', 'unique'],
        ];
    }

    // ------------------------------------------------------------------
    // IdentityInterface — contrato exigido pelo componente `user` do Yii
    // ------------------------------------------------------------------

    public static function findIdentity($id): ?IdentityInterface
    {
        return static::findOne($id);
    }

    /**
     * Não usamos "access token" persistido: o token é um JWT stateless,
     * validado a cada requisição pelo componente JwtAuth. Por isso, retorna null.
     */
    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface
    {
        return null;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    // authKey é usado por login baseado em cookie "lembrar-me". API stateless não precisa.
    public function getAuthKey(): ?string
    {
        return null;
    }

    public function validateAuthKey($authKey): bool
    {
        return false;
    }

    // ------------------------------------------------------------------
    // Regras de negócio próprias do usuário
    // ------------------------------------------------------------------

    public static function findByEmail(string $email): ?self
    {
        return static::findOne(['email' => $email]);
    }

    /**
     * Nunca armazenamos a senha em texto puro: guardamos o hash (bcrypt via
     * componente de segurança do Yii). Analogia C#: PasswordHasher do Identity.
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Relação 1:N com Expense. Analogia C#: propriedade de navegação no EF Core.
     */
    public function getExpenses(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Expense::class, ['user_id' => 'id']);
    }

    /**
     * Controla quais campos são expostos ao serializar em JSON.
     * IMPORTANTE: password_hash é deliberadamente omitido — nunca deve vazar na API.
     */
    public function fields(): array
    {
        return ['id', 'email', 'created_at'];
    }
}
