<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace valentinek\tests\models;

use valentinek\behaviors\ClosureTable;

class CategoryTest extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'category';
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
} 