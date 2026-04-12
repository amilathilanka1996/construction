<?php

namespace app\controllers;

use app\models\Company;
use app\models\User;
use app\models\UserCompany;

class CompanyController extends ApiController
{
    public function actionIndex(): array
    {
        $user = $this->getAuthUser();

        if ($user->role === User::ROLE_SUPERADMIN) {
            $companies = Company::find()->orderBy(['name' => SORT_ASC])->all();
        } else {
            $currentCompanyId = (int) ($user->getAttribute('company_id') ?: 0);
            $membershipCompanyIds = UserCompany::find()
                ->select('company_id')
                ->where(['user_id' => $user->id])
                ->column();

            $companyIds = array_values(array_unique(array_filter(array_merge(
                $currentCompanyId ? [$currentCompanyId] : [],
                array_map('intval', $membershipCompanyIds)
            ))));

            $companies = empty($companyIds)
                ? []
                : Company::find()->where(['id' => $companyIds])->orderBy(['name' => SORT_ASC])->all();
        }

        return [
            'companies' => $companies,
            'selected_company_id' => $this->getSelectedCompanyId(false),
        ];
    }

    public function actionCreate(): array
    {
        $user = $this->getAuthUser();
        $model = new Company();
        $model->load(\Yii::$app->request->bodyParams, '');

        if (!$model->save()) {
            \Yii::$app->response->statusCode = 422;
            return ['message' => 'Company creation failed.', 'errors' => $model->errors];
        }

        $membership = new UserCompany();
        $membership->user_id = $user->id;
        $membership->company_id = $model->id;
        $membership->save();

        if (!$user->getAttribute('company_id')) {
            $user->company_id = $model->id;
            $user->save(false, ['company_id']);
        }

        return [
            'message' => 'Company created successfully.',
            'company' => $model,
        ];
    }
}