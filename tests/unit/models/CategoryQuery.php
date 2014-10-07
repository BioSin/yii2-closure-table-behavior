<?php

namespace valentinek\tests\unit\models;

use yii\db\ActiveQuery;
use valentinek\behaviors\ClosureTableQuery;

class CategoryQuery extends ActiveQuery
{

    public function behaviors()
    {
        return [
            [
                'class' => ClosureTableQuery::className(),
                'tableName' => 'category_tree'
            ],
        ];
    }
}