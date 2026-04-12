<?php

namespace app\controllers;

use app\models\ApiLoginForm;
use app\models\SignupForm;
use app\models\User;
use yii\web\BadRequestHttpException;

class AuthController extends ApiController
{
    public function actionSignup(): array
    {
        $body = \Yii::$app->request->bodyParams;
        $actor = $this->findActor();

        if (User::find()->exists()) {
            $requestedRole = $body['role'] ?? User::ROLE_USER;
            if ($requestedRole === User::ROLE_SUPERADMIN && (!$actor || $actor->role !== User::ROLE_SUPERADMIN)) {
                throw new BadRequestHttpException('Only superadmin can create another superadmin.');
            }
            if (!$actor || $actor->role !== User::ROLE_SUPERADMIN) {
                $body['role'] = User::ROLE_USER;
            }
            if (empty($body['company_id']) && $actor && $actor->company_id) {
                $body['company_id'] = (int) $actor->company_id;
            }
        } else {
            $body['role'] = User::ROLE_SUPERADMIN;
        }

        $model = new SignupForm();
        $model->load($body, '');

        $user = $model->signup();
        if (!$user) {
            \Yii::$app->response->statusCode = 422;
            return ['message' => 'Signup failed.', 'errors' => $model->errors];
        }
        return [
            'message' => 'User created successfully.',
            'token' => $user->access_token,
            'user' => $user,
        ];
    }

    private function findActor(): ?User
    {
        $header = \Yii::$app->request->headers->get('Authorization', '');
        $token = preg_replace('/^Bearer\s+/i', '', $header);

        if (!$token) {
            return null;
        }

        return User::findIdentityByAccessToken($token);
    }

    public function actionLogin(): array
    {
        $model = new ApiLoginForm();
        $model->load(\Yii::$app->request->bodyParams, '');
        $user = $model->login();

        if (!$user) {
            \Yii::$app->response->statusCode = 422;
            return ['message' => 'Login failed.', 'errors' => $model->errors];
        }

        return [
            'message' => 'Login successful.',
            'token' => $user->access_token,
            'user' => $user,
        ];
    }

    public function actionMe(): array
    {
        return [
            'user' => $this->getAuthUser(),
        ];
    }

    public function actionLogout(): array
    {
        $user = $this->getAuthUser();
        $user->access_token = null;
        $user->updated_at = date('Y-m-d H:i:s');
        $user->save(false, ['access_token', 'updated_at']);

        return ['message' => 'Logout successful.'];
    }
}