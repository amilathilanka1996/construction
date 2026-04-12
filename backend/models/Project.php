<?php

namespace app\models;

use yii\db\ActiveRecord;

class Project extends ActiveRecord
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_RETENTION = 'retention';
    public const STATUS_CLOSED = 'closed';

    public static function tableName(): string
    {
        return '{{%projects}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'company_id', 'name', 'description', 'created_date', 'start_date', 'final_date', 'status', 'estimate_amount', 'valuation_amount'], 'required'],
            [['user_id', 'company_id'], 'integer'],
            [['description'], 'string'],
            [['estimate_amount', 'valuation_amount'], 'number', 'min' => 0],
            [['created_date', 'start_date', 'final_date', 'status_changed_at', 'created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => [self::STATUS_RUNNING, self::STATUS_RETENTION, self::STATUS_CLOSED]],
        ];
    }

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['owner_name'] = fn () => $this->user ? $this->user->name : null;
        $fields['company_name'] = fn () => $this->company ? $this->company->name : null;
        $fields['expense_total'] = fn () => (float) $this->getExpenses()->sum('amount');
        $fields['income_total'] = fn () => (float) $this->getIncomes()->sum('amount');
        $fields['balance'] = fn () => ((float) $this->getIncomes()->sum('amount')) - ((float) $this->getExpenses()->sum('amount'));
        $fields['file_count'] = fn () => (int) $this->getFiles()->count();
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

    public function getExpenses()
    {
        return $this->hasMany(ProjectExpense::class, ['project_id' => 'id'])->orderBy(['entry_date' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function getIncomes()
    {
        return $this->hasMany(ProjectIncome::class, ['project_id' => 'id'])->orderBy(['entry_date' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function getFiles()
    {
        return $this->hasMany(ProjectFile::class, ['project_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }
}