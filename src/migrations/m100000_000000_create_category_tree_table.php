<?php

use yii\db\Schema;
use yii\db\Migration;

class m100000_000000_create_category_tree_table extends Migration
{
    // Closure table name
    public $closureTbl = "category_tree";

    // Name of the table to which you connect the behavior
    public $relativeTbl = "category";

    public function up()
    {
        $this->createTable("{{%{$this->closureTbl}}}", [
            "parent" => Schema::TYPE_INTEGER . " NOT NULL",
            "child" => Schema::TYPE_INTEGER . " NOT NULL",
            "depth" => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
        ]);
        $this->addPrimaryKey("PK_{$this->closureTbl}", "{{%{$this->closureTbl}}}", ["parent", "child"]);
        $this->createIndex("FK_{$this->closureTbl}_child_{$this->relativeTbl}", "{{%{$this->closureTbl}}}", "child");
        $this->addForeignKey("FK_{$this->closureTbl}_child_{$this->relativeTbl}",
            "{{%{$this->closureTbl}}}", "child",
            "{{%{$this->relativeTbl}}}", "id",
            "CASCADE"
        );
        $this->addForeignKey("FK_{$this->closureTbl}_parent_{$this->relativeTbl}",
            "{{%{$this->closureTbl}}}", "parent",
            "{{%{$this->relativeTbl}}}", "id",
            "CASCADE"
        );

        // Optional, make first category as root category
        // Pleas delete that or change to your model class and ID
        // Also you must connect behavior before run migration
        \common\models\Category::findOne(1)->saveNodeAsRoot(false);
    }

    public function down()
    {
        $this->dropForeignKey("FK_{$this->closureTbl}_parent_{$this->relativeTbl}", "{{%{$this->closureTbl}}}");
        $this->dropForeignKey("FK_{$this->closureTbl}_child_{$this->relativeTbl}", "{{%{$this->closureTbl}}}");
        $this->dropTable("{{%{$this->closureTbl}}}");
    }
}
