<?php

use yii\codeception\DbTestCase;
use valentinek\tests\unit\fixtures\CategoryFixture;
use valentinek\tests\unit\fixtures\RelatedFixture;
use valentinek\tests\unit\fixtures\CategoryTreeFixture;
use valentinek\tests\unit\models\Category;
use valentinek\tests\unit\models\Related;

class ClosureTableBehaviorTest extends DbTestCase
{
    use \Codeception\Specify;

    public $appConfig = '@tests/unit/unit.php';

    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function fixtures()
    {
        return [
            'categories' => CategoryFixture::className(),
            'category_related' => RelatedFixture::className(),
            'category_tree' => CategoryTreeFixture::className()
        ];
    }

    // tests
    public function testRoots()
    {
        $this->specify("The category exist", function () {
            $category = Category::findOne(1);
            $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
        });

        $rootCategories = Category::find()->roots()->all();

        $this->specify("and only one root category", function () use ($rootCategories) {
            $this->assertEquals(1, count($rootCategories));
        });

        $this->specify("and one ancestor", function () use ($rootCategories) {
            $ancestorsCount = 0;
            foreach ($rootCategories as $category) {
                $ancestorsCount += $category->ancestors()->count();
            }
            $this->assertEquals(0, $ancestorsCount);
        });
    }

    public function testDescendants()
    {
        $category = Category::findOne(1);

        $this->specify("The category exist", function () use ($category) {
            $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
        });

        $this->specify("and 6 active descendants", function () use ($category) {
            $descendants = $category->descendants()->all();
            $this->assertEquals(6, count($descendants));
            foreach ($descendants as $descendant) {
                $this->assertInstanceOf('valentinek\tests\unit\models\Category', $descendant);
            }
            $this->assertEquals(2, $descendants[0]->primaryKey);
            $this->assertEquals(3, $descendants[1]->primaryKey);
            $this->assertEquals(4, $descendants[2]->primaryKey);
            $this->assertEquals(5, $descendants[3]->primaryKey);
            $this->assertEquals(6, $descendants[4]->primaryKey);
            $this->assertEquals(7, $descendants[5]->primaryKey);
        });
    }

    public function testDescendantsOf()
    {
        $descendantsOf = Category::find()->descendantsOf(1)->all();
        $category = Category::findOne(1);

        $this->specify("The category exist", function () use ($category) {
            $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
        });

        $descendants = $category->descendants()->all();

        $this->specify("and descendants are exist and equals", function () use ($descendants, $descendantsOf) {
            $this->assertEquals(6, count($descendants));

            foreach ($descendants as $key => $row) {
                $this->assertEquals($descendantsOf[$key], $row);
            }
        });
    }

    public function testChildren()
    {
        $this->specify("Root category have two valid children", function () {
            $children = Category::find()->childrenOf(1)->all();

            $this->assertEquals(2, count($children));

            foreach ($children as $child) {
                $this->assertInstanceOf('valentinek\tests\unit\models\Category', $child);
            }

            $this->assertEquals(2, $children[0]->primaryKey);
            $this->assertEquals(4, $children[1]->primaryKey);
        });
    }

    public function testAncestors()
    {
        $category = Category::findOne(6);
        $this->specify("There are instanceOf Category class", function () use ($category) {
            $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
        });

        $this->specify("with two ancestors with id 1 and 4", function () use ($category) {
            $ancestors = $category->ancestors()->all();
            $this->assertEquals(2, count($ancestors));
            foreach ($ancestors as $ancestor) {
                $this->assertInstanceOf('valentinek\tests\unit\models\Category', $ancestor);
            }
            $this->assertEquals(1, $ancestors[0]->primaryKey);
            $this->assertEquals(4, $ancestors[1]->primaryKey);
        });
    }

    public function testParent()
    {
        $category = Category::findOne(6);
        $this->specify("There are instanceOf Category class", function () use ($category) {
            $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
        });

        $this->specify("which have parent with id = 4.", function () use ($category) {
            $parent = $category->parent()->one();
            $this->assertInstanceOf('valentinek\tests\unit\models\Category', $parent);
            $this->assertEquals(4, $parent->primaryKey);
        });
    }

    public function testPath()
    {
        $this->specify("There are model with id = 1 and with one path element with id = 1.", function () {
            $categories = Category::find()->pathOf(1)->all();
            $this->assertEquals(1, count($categories));
            $this->assertEquals(1, $categories[0]->primaryKey);
        });
        $this->specify("There are model with id = 7 and with 4 path elements.", function () {
            $categories = Category::find()->pathOf(7)->all();
            $this->assertEquals(4, count($categories));
            $this->assertEquals(1, $categories[0]->primaryKey);
            $this->assertEquals(4, $categories[1]->primaryKey);
            $this->assertEquals(6, $categories[2]->primaryKey);
            $this->assertEquals(7, $categories[3]->primaryKey);
        });
    }

    public function testFullPath()
    {
        $this->specify("There are model with id = 4 and with 4 fullpath elements.", function () {
            $categories = Category::find()->fullPathOf(4)->all();
            $this->assertEquals(4, count($categories));
            $this->assertEquals(2, $categories[0]->primaryKey);
            $this->assertEquals(4, $categories[1]->primaryKey);
            $this->assertEquals(5, $categories[2]->primaryKey);
            $this->assertEquals(6, $categories[3]->primaryKey);
        });
    }

    public function testIsLeaf()
    {
        $this->specify("isLeaf() method return true for leaf categories.", function () {
            $leafs = array(3, 5, 7);
            foreach ($leafs as $id) {
                $category = Category::find()->where(['id' => $id])->leaf()->one();
                $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
                $this->assertNotEmpty($category->id);
                $this->assertTrue($category->isLeaf());
            };
        });

        $this->specify("and false for not leaf categories.", function () {
            $notLeafs = array(1, 2, 4, 6);
            foreach ($notLeafs as $id) {
                $category = Category::find()->where(['id' => $id])->leaf()->one();
                $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
                $this->assertNotEmpty($category->id);
                $this->assertFalse($category->isLeaf());
            };
        });
    }

    public function testChildrenIsLeaf()
    {
        $this->specify("Category with id 4 have two leaf categories.", function() {
            $categories = Category::find()->leaf()->childrenOf(4)->all();
            $this->assertEquals(5, $categories[0]->primaryKey);
            $this->assertTrue($categories[0]->isLeaf());
            $this->assertEquals(6, $categories[1]->primaryKey);
            $this->assertFalse($categories[1]->isLeaf());
        });

        $this->specify("Category with id 1 have two leaf categories.", function() {
            $categories = Category::find()->leaf()->childrenOf(1)->all();
            $this->assertEquals(2, count($categories));
        });
    }

    public function testAppend()
    {

        $category = Category::findOne(5);
        $newCategory = new Category();
        $newCategory->name = 'Category 1.4.5.8';
        $this->assertTrue($newCategory->save());
        $this->assertGreaterThan(0, $category->append($newCategory));

        $parent = $newCategory->parent()->one();
        $this->assertInstanceOf('valentinek\tests\unit\models\Category', $parent);
        $this->assertEquals(5, $parent->primaryKey);

        $parent = $parent->parent()->one();
        $this->assertInstanceOf('valentinek\tests\unit\models\Category', $parent);
        $this->assertEquals(4, $parent->primaryKey);

        $parent = $parent->parent()->one();
        $this->assertInstanceOf('valentinek\tests\unit\models\Category', $parent);
        $this->assertEquals(1, $parent->primaryKey);
    }

    public function testMoveTo()
    {
        $categories = Category::find()->descendantsOf(5)->all();
        $this->assertEquals(0, count($categories));
        Category::find()->moveTo(5, 2);

        $categories = Category::find()->descendantsOf(5)->all();
        $this->assertEquals(2, count($categories));
        $this->assertEquals(2, $categories[0]->primaryKey);
        $this->assertEquals(3, $categories[1]->primaryKey);

        $categories = Category::find()->descendantsOf(2)->all();
        $this->assertEquals(1, count($categories));
        $this->assertEquals(3, $categories[0]->primaryKey);

        $parent = Category::find()->parentOf(2)->one();
        $this->assertInstanceOf('valentinek\tests\unit\models\Category', $parent);
        $this->assertEquals(5, $parent->primaryKey);
    }

    public function testMoveToInvalid()
    {
        // from -> to
        $moves = array(
            array(1, 0), array(0, 1), array(0, 0), array(3, 0),
            array(1, 1), array(1, 2), array(1, 3), array(1, 7),
            array(2, 3), array(2, 2),
            array(3, 3),
        );
        foreach ($moves as $move) {
            try {
                Category::find()->moveTo($move[1], $move[0]);
                $this->fail();
            } catch (\Exception $e) {}
        }
    }

    public function testDeleteNode()
    {
        $category = Category::findOne(4);
        $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
        $category->deleteNode();

        $category = Category::findOne(4);
        $this->assertNull($category);
        $category = Category::findOne(5);
        $this->assertNull($category);
        $category = Category::findOne(6);
        $this->assertNull($category);
        $category = Category::findOne(7);
        $this->assertNull($category);
        $category = Category::findOne(2);
        $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);
        $category = Category::findOne(3);
        $this->assertInstanceOf('valentinek\tests\unit\models\Category', $category);

        $this->assertEquals(0, Category::find()->deleteNode(0));

        $this->assertEquals(3, Category::find()->deleteNode(1));
        $this->assertEquals(0, count(Category::find()->all()));
    }

    public function testSaveAsRoot()
    {
        $newCategory = new Category();
        $newCategory->name = 'Category 1';
        $this->assertTrue($newCategory->saveNodeAsRoot());
        $this->assertEquals(0, count($newCategory->descendants()->all()));
        $this->assertEquals(0, count($newCategory->ancestors()->all()));
    }

    public function testRelations()
    {
        $related = Related::find()->where(['id'=>1])->with(["parent"=>function($query){$query->leaf();}])->one();
        $this->assertNotNull($related);
        $this->assertNotNull($related->parent);
        $this->assertFalse($related->parent->isLeaf());

        $related = Related::find()->where(['id'=>2])->with(["parent"=>function($query){$query->leaf();}])->one();
        $this->assertNotNull($related);
        $this->assertNotNull($related->parent);
        $this->assertTrue($related->parent->isLeaf());
    }

    public function testMixed()
    {
        $category5 = Category::findOne(5);
        $newFolder = new Category();
        $newFolder->name = 'Category 1.4.5.8';
        $this->assertTrue($newFolder->save());
        $this->assertGreaterThan(0, $category5->append($newFolder));

        $category5->moveTo(2);

        $category2Childs = Category::find()->childrenOf(2)->all();
        $this->assertEquals(2, count($category2Childs));

        $category2Descendants = Category::find()->descendantsOf(2)->all();
        $this->assertEquals(3, count($category2Descendants));

        $categories = Category::find()->pathOf(8)->all();
        $this->assertEquals(4, count($categories));
        $this->assertEquals(1, $categories[0]->primaryKey);
        $this->assertEquals(2, $categories[1]->primaryKey);
        $this->assertEquals(5, $categories[2]->primaryKey);
        $this->assertEquals(8, $categories[3]->primaryKey);
    }

    public function testQuoting()
    {
        $this->assertEmpty(Category::findOne("'"));
        $this->assertEmpty(Category::find()->ancestorsOf("'")->all());
        $this->assertEmpty(Category::find()->childrenOf("'")->all());
        $this->assertEmpty(Category::find()->parentOf("'")->all());
        $this->assertEmpty(Category::find()->deleteNode("'"));
        $this->assertEmpty(Category::find()->descendantsOf("'")->all());
        $this->assertEmpty(Category::find()->fullPathOf("'")->all());
        $this->assertEmpty(Category::find()->pathOf("'")->all());
        $this->assertEmpty(Category::find()->unorderedPathOf("'")->all());

        /** @var Category $folder5 */
        $category5 = Category::findOne(5);
        try {
            $category5->moveTo("'");
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals(201, $e->getCode());
        }
        $newCategory = new Category();
        $newCategory->name = 'Category';
        $this->assertTrue($newCategory->save());
        $this->assertEquals(1, $newCategory->appendTo("'"));
        try {
            Category::find()->markAsRoot("'");
            $this->fail();
        } catch (\Exception $e) {
            // http://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
            $this->assertEquals('1452', $e->errorInfo[1]);
        }
    }
}