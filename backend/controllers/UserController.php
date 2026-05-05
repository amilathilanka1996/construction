<?php

namespace app\controllers;

use app\models\Company;
use app\models\User;
use app\models\UserCompany;
use yii\web\NotFoundHttpException;

class UserController extends ApiController
{
    public function actionIndex(): array
    {
        $this->requireSuperadmin();

        return [
            'users' => User::find()->with(['company', 'companies'])->orderBy(['id' => SORT_DESC])->all(),
            'companies' => Company::find()->orderBy(['name' => SORT_ASC])->all(),
        ];
    }

    public function actionUpdate(int $id): array
    {
        $actor = $this->requireSuperadmin();
        $user = User::findOne($id);

        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }

        if ((int) $actor->id === (int) $user->id && (\Yii::$app->request->bodyParams['role'] ?? $user->role) !== User::ROLE_SUPERADMIN) {
            \Yii::$app->response->statusCode = 422;
            return ['message' => 'You cannot remove your own superadmin role.'];
        }

        $body = \Yii::$app->request->bodyParams;
        $companyIds = array_values(array_unique(array_map('intval', $body['company_ids'] ?? [])));
        $primaryCompanyId = isset($body['company_id']) && $body['company_id'] !== '' ? (int) $body['company_id'] : null;
        $password = trim((string) ($body['password'] ?? ''));

        unset($body['company_ids'], $body['password']);
        $user->load($body, '');

        if ($password !== '') {
            $user->setPassword($password);
        }

        if ($primaryCompanyId && !in_array($primaryCompanyId, $companyIds, true)) {
            $companyIds[] = $primaryCompanyId;
        }

        if (!$primaryCompanyId && !empty($companyIds)) {
            $primaryCompanyId = $companyIds[0];
        }

        $user->company_id = $primaryCompanyId;

        $transaction = \Yii::$app->db->beginTransaction();

        try {
            if (!$user->save()) {
                $transaction->rollBack();
                \Yii::$app->response->statusCode = 422;
                return ['message' => 'User update failed.', 'errors' => $user->errors];
            }

            UserCompany::deleteAll(['user_id' => $user->id]);
            foreach ($companyIds as $companyId) {
                if (!Company::find()->where(['id' => $companyId])->exists()) {
                    continue;
                }

                $membership = new UserCompany();
                $membership->user_id = $user->id;
                $membership->company_id = $companyId;
                $membership->save();
            }

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        return [
            'message' => 'User updated successfully.',
            'user' => User::find()->with(['company', 'companies'])->where(['id' => $user->id])->one(),
        ];
    }
}