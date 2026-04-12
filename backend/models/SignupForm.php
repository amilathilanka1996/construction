<?php

namespace app\models;

use yii\base\Model;

class SignupForm extends Model
{
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $role = User::ROLE_USER;
    public ?int $company_id = null;

    public function rules(): array
    {
        return [
            [['name', 'username', 'email', 'password'], 'required'],
            [['name', 'username', 'email', 'password'], 'trim'],
            ['email', 'email'],
            ['role', 'in', 'range' => [User::ROLE_SUPERADMIN, User::ROLE_USER]],
            [['name', 'username', 'email'], 'string', 'max' => 255],
            ['password', 'string', 'min' => 6],
            ['company_id', 'integer'],
            ['username', 'validateUniqueUsername'],
            ['email', 'validateUniqueEmail'],
        ];
    }

    public function validateUniqueUsername(): void
    {
        if (!$this->hasErrors() && User::find()->where(['username' => $this->username])->exists()) {
            $this->addError('username', 'Username already exists.');
        }
    }

    public function validateUniqueEmail(): void
    {
        if (!$this->hasErrors() && User::find()->where(['email' => $this->email])->exists()) {
            $this->addError('email', 'Email already exists.');
        }
    }

    public function signup(): ?User
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->name = $this->name;
        $user->username = $this->username;
        $user->email = $this->email;
        $user->role = $this->role;
        $user->company_id = $this->company_id;
        $user->status = 1;
        $user->generateAuthKey();
        $user->generateAccessToken();
        $user->setPassword($this->password);

        if (!$user->save()) {
            return null;
        }

        if ($this->company_id) {
            $membership = new UserCompany();
            $membership->user_id = $user->id;
            $membership->company_id = $this->company_id;
            $membership->save();
        }

        return $user;
    }
}