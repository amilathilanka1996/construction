<?php

namespace app\models;

use yii\db\ActiveRecord;

class UserCompany extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%user_companies}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'company_id'], 'required'],
            [['user_id', 'company_id'], 'integer'],
            [['created_at'], 'safe'],
            [['user_id', 'company_id'], 'unique', 'targetAttribute' => ['user_id', 'company_id']],
        ];
    }
}