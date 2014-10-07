<?php
return yii\helpers\ArrayHelper::merge(
    require(YII_APP_BASE_PATH . '/common/config/main.php'),
    require(YII_APP_BASE_PATH . '/common/config/main-local.php'),
    [
        'id' => 'app-test',
        'basePath' => dirname(__DIR__),
        'components' => [
            'db' => [
                'class' => 'yii\db\Connection',
                'dsn' => 'mysql:host=localhost;dbname=stoma_test',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8',
            ],
        ]
    ]
);