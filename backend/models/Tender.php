<?php

namespace app\models;

use yii\db\ActiveRecord;

class Tender extends ActiveRecord
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public static function tableName(): string
    {
        return '{{%tenders}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'company_id', 'name', 'description', 'start_date', 'end_date', 'created_date', 'bid_security_deposit', 'performance_security_deposit', 'status'], 'required'],
            [['user_id', 'company_id'], 'integer'],
            [['description'], 'string'],
            [['bid_security_deposit', 'performance_security_deposit'], 'number', 'min' => 0],
            [['start_date', 'end_date', 'created_date', 'created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => [self::STATUS_OPEN, self::STATUS_CLOSED]],
        ];
    }

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['company_name'] = fn () => $this->company ? $this->company->name : null;
        return $fields;
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getCompany()
    {
        return $this->hasOne(Company::class, ['id' => 'company_id']);
    }

    public function getFiles()
    {
        return $this->hasMany(TenderFile::class, ['tender_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }
}