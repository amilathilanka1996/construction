<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'zFhygDPB963Pa7wj8OQEfR8irCeqqICD',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
            'loginUrl' => null,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'OPTIONS api' => 'site/options',
                'OPTIONS api/<path:.+>' => 'site/options',
                'POST api/signup' => 'auth/signup',
                'POST api/login' => 'auth/login',
                'GET api/me' => 'auth/me',
                'POST api/logout' => 'auth/logout',
                'GET api/dashboard' => 'dashboard/index',
                'GET api/companies' => 'company/index',
                'POST api/companies' => 'company/create',
                'GET api/users' => 'user/index',
                'PATCH api/users/<id:\d+>' => 'user/update',
                'GET api/projects' => 'project/index',
                'POST api/projects' => 'project/create',
                'GET api/projects/<id:\d+>' => 'project/view',
                'PATCH api/projects/<id:\d+>' => 'project/update',
                'PATCH api/projects/<id:\d+>/status' => 'project/update-status',
                'POST api/projects/<id:\d+>/expenses' => 'project/add-expense',
                'POST api/projects/<id:\d+>/incomes' => 'project/add-income',
                'GET api/tenders' => 'tender/index',
                'POST api/tenders' => 'tender/create',
                'GET api/tenders/<id:\d+>' => 'tender/view',
                'PATCH api/tenders/<id:\d+>' => 'tender/update',
                'PATCH api/tenders/<id:\d+>/status' => 'tender/update-status',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
