<?php

namespace app\models;

use yii\db\ActiveRecord;

class Company extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%companies}}';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name', 'description'], 'trim'],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique'],
        ];
    }

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['project_count'] = fn () => (int) $this->getProjects()->count();
        $fields['tender_count'] = fn () => (int) $this->getTenders()->count();
        $fields['user_count'] = fn () => (int) $this->getUsers()->count();
        return $fields;
    }

    public function getMemberships()
    {
        return $this->hasMany(UserCompany::class, ['company_id' => 'id']);
    }

    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])->via('memberships');
    }

    public function getProjects()
    {
        return $this->hasMany(Project::class, ['company_id' => 'id']);
    }

    public function getTenders()
    {
        return $this->hasMany(Tender::class, ['company_id' => 'id']);
    }
}