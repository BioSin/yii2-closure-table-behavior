<?php

namespace valentinek\tests\unit\models;

use yii\db\ActiveRecord;

class Related extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%category_related}}';
    }

    public function getParent() {
        return $this->hasOne(Category::className(), ['id' => 'parent_category_id']);
    }
}