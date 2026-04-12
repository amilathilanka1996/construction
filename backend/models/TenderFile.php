<?php

namespace app\models;

use yii\db\ActiveRecord;

class TenderFile extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%tender_files}}';
    }

    public function rules(): array
    {
        return [
            [['tender_id', 'user_id', 'original_name', 'stored_name', 'file_path'], 'required'],
            [['tender_id', 'user_id', 'file_size'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['original_name', 'stored_name', 'file_path', 'file_type'], 'string', 'max' => 255],
        ];
    }

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['file_url'] = function () {
            if (!\Yii::$app->has('request')) {
                return $this->file_path;
            }

            return rtrim(\Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl, '/') . '/' . ltrim($this->file_path, '/');
        };

        return $fields;
    }
}