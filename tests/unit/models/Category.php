<?php

namespace valentinek\tests\unit\models;

use yii\db\ActiveRecord;
use valentinek\behaviors\ClosureTable;

class Category extends ActiveRecord
{

    public $leaf;

    public static function tableName()
    {
        return '{{%category}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => ClosureTable::className(),
                'tableName' => 'category_tree'
            ],
        ];
    }

    public static function find()
    {
        return new CategoryQuery(static::className());
    }
}