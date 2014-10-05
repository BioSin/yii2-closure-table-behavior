<?php

use yii\codeception\DbTestCase;
use valentinek\tests\fixtures\CategoryFixture;

class ClosureTableBehaviorTest extends DbTestCase
{

    public $appConfig = '@app/console/config/main.php';

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
            'categories' => CategoryFixture::className()
        ];
    }

    // tests
    public function testRoots()
    {
        $this->assertTrue(true);
//        var_dump($this->categories);
    }

    public function testDescendants()
    {

    }

    public function testDescentantsOf()
    {

    }

    public function testQuoting()
    {

    }
}