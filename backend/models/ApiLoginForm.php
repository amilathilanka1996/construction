<?php

namespace app\models;

use yii\base\Model;

class ApiLoginForm extends Model
{
    public string $username = '';
    public string $password = '';

    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
        ];
    }

    public function login(): ?User
    {
        if (!$this->validate()) {
            return null;
        }

        $user = User::findByUsername($this->username);
        if (!$user || !$user->validatePassword($this->password)) {
            $this->addError('password', 'Invalid username or password.');
            return null;
        }

        $user->generateAccessToken();
        $user->updated_at = date('Y-m-d H:i:s');
        $user->save(false, ['access_token', 'updated_at']);

        return $user;
    }
}
