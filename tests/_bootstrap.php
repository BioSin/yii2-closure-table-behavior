<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// This is global bootstrap for autoloading

require(__DIR__ . '/../../../autoload.php');
require(__DIR__ . '/../../../yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../../../common/config/bootstrap.php');

Yii::setAlias('@tests', __DIR__);
Yii::setAlias('@app', __DIR__ . '/../../../../');
