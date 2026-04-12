<?php

namespace app\controllers;

use app\models\Company;
use app\models\User;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

class ApiController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false,
            ],
        ];

        return $behaviors;
    }

    public function beforeAction($action): bool
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        if (\Yii::$app->request->isOptions) {
            \Yii::$app->response->statusCode = 200;
            return false;
        }

        return parent::beforeAction($action);
    }

    protected function getAuthUser(): User
    {
        $header = \Yii::$app->request->headers->get('Authorization', '');
        $token = preg_replace('/^Bearer\s+/i', '', $header);

        if (!$token) {
            throw new UnauthorizedHttpException('Missing access token.');
        }

        $user = User::findIdentityByAccessToken($token);
        if (!$user) {
            throw new UnauthorizedHttpException('Invalid access token.');
        }

        return $user;
    }

    protected function requireSuperadmin(): User
    {
        $user = $this->getAuthUser();
        if ($user->role !== User::ROLE_SUPERADMIN) {
            throw new ForbiddenHttpException('Superadmin access required.');
        }

        return $user;
    }

    protected function getSelectedCompanyId(bool $required = true): ?int
    {
        $user = $this->getAuthUser();
        $headerValue = \Yii::$app->request->headers->get('X-Company-Id', '');
        $queryValue = \Yii::$app->request->get('company_id', '');
        $rawCompanyId = $headerValue !== '' ? $headerValue : $queryValue;
        $currentCompanyId = $user->getAttribute('company_id');

        if (($rawCompanyId === '' || $rawCompanyId === null) && $currentCompanyId) {
            $rawCompanyId = $currentCompanyId;
        }

        if ($rawCompanyId === '' || $rawCompanyId === null) {
            $firstCompanyId = $this->getFirstAvailableCompanyId($user);
            if ($firstCompanyId) {
                return $firstCompanyId;
            }

            if ($required) {
                throw new BadRequestHttpException('Please select a company.');
            }

            return null;
        }

        $companyId = (int) $rawCompanyId;
        if ($companyId <= 0) {
            if ($required) {
                throw new BadRequestHttpException('Invalid company selection.');
            }
            return null;
        }

        if ($user->role !== User::ROLE_SUPERADMIN) {
            $hasAccess = $user->getCompanies()->andWhere(['companies.id' => $companyId])->exists();
            if (!$hasAccess) {
                throw new ForbiddenHttpException('You do not have access to this company.');
            }
        } elseif (!Company::find()->where(['id' => $companyId])->exists()) {
            throw new BadRequestHttpException('Selected company was not found.');
        }

        return $companyId;
    }

    private function getFirstAvailableCompanyId(User $user): ?int
    {
        if ($user->role === User::ROLE_SUPERADMIN) {
            $company = Company::find()->orderBy(['id' => SORT_ASC])->one();
            return $company ? (int) $company->id : null;
        }

        $company = $user->getCompanies()->orderBy(['companies.id' => SORT_ASC])->one();
        return $company ? (int) $company->id : null;
    }
}