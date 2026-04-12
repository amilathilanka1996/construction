<?php

namespace app\models;

use yii\db\ActiveRecord;

class ProjectExpense extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%project_expenses}}';
    }

    public function rules(): array
    {
        return [
            [['project_id', 'user_id', 'title', 'quantity', 'unit_price'], 'required'],
            [['project_id', 'user_id'], 'integer'],
            [['amount', 'quantity', 'unit_price'], 'number', 'min' => 0],
            [['details'], 'string'],
            [['entry_date', 'created_at', 'updated_at'], 'safe'],
            [['title', 'reference_no'], 'string', 'max' => 255],
        ];
    }

    public function beforeValidate(): bool
    {
        if (empty($this->entry_date)) {
            $this->entry_date = date('Y-m-d');
        }

        if ($this->quantity !== null && $this->unit_price !== null) {
            $this->amount = (float) $this->quantity * (float) $this->unit_price;
        }

        return parent::beforeValidate();
    }
}
