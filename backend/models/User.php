<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_USER = 'user';

    public static function tableName(): string
    {
        return '{{%users}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'username', 'email', 'password_hash', 'auth_key', 'role'], 'required'],
            [['status', 'company_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'username', 'email', 'auth_key', 'access_token'], 'string', 'max' => 255],
            [['role'], 'in', 'range' => [self::ROLE_SUPERADMIN, self::ROLE_USER]],
            [['username'], 'unique'],
            [['email'], 'unique'],
        ];
    }

    public function fields(): array
    {
        return [
            'id',
            'name',
            'username',
            'email',
            'role',
            'status',
            'company_id' => fn () => $this->getAttribute('company_id'),
            'company_name' => fn () => $this->company ? $this->company->name : null,
            'company_ids' => fn () => $this->getCompanies()->select('companies.id')->column(),
            'company_names' => fn () => $this->getCompanies()->select('companies.name')->column(),
            'created_at',
        ];
    }

    public static function findIdentity($id): ?self
    {
        return static::findOne(['id' => $id, 'status' => 1]);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return static::findOne(['access_token' => $token, 'status' => 1]);
    }

    public static function findByUsername(string $username): ?self
    {
        return static::findOne(['username' => $username, 'status' => 1]);
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getAuthKey(): string
    {
        return (string) $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return \Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function generateAccessToken(): string
    {
        $this->access_token = \Yii::$app->security->generateRandomString(64);
        return $this->access_token;
    }

    public function generateAuthKey(): string
    {
        $this->auth_key = \Yii::$app->security->generateRandomString();
        return $this->auth_key;
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = \Yii::$app->security->generatePasswordHash($password);
    }

    public function getProjects()
    {
        return $this->hasMany(Project::class, ['user_id' => 'id']);
    }

    public function getCompany()
    {
        return $this->hasOne(Company::class, ['id' => 'company_id']);
    }

    public function getMemberships()
    {
        return $this->hasMany(UserCompany::class, ['user_id' => 'id']);
    }

    public function getCompanies()
    {
        return $this->hasMany(Company::class, ['id' => 'company_id'])->via('memberships');
    }
}