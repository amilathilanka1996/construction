<?php

namespace app\models;

use yii\db\ActiveRecord;

class ProjectIncome extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%project_incomes}}';
    }

    public function rules(): array
    {
        return [
            [['project_id', 'user_id', 'title', 'amount', 'entry_date'], 'required'],
            [['project_id', 'user_id'], 'integer'],
            [['amount'], 'number', 'min' => 0],
            [['details'], 'string'],
            [['entry_date', 'created_at', 'updated_at'], 'safe'],
            [['title', 'reference_no'], 'string', 'max' => 255],
        ];
    }
}
