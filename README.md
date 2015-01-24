Closure table behavior for Yii2
===============================
Yii2 port of the [yii-closure-table-behavior](https://github.com/AidasK/yii-closure-table-behavior).
Extension allows managing trees stored in database via closure-table method.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```sh
php composer.phar require valentinek/yii2-closure-table-behavior "*"
```

or add

```json
"valentinek/yii2-closure-table-behavior": "*"
```

to the require section of your `composer.json` file.

Configuring
--------------------------

First you need to configure model as follows:

```php
class Category extends ActiveRecord
{
    public $leaf;

	public function behaviors() {
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
```

Second you need to configure query model as follows:

```php
class CategoryQuery extends ActiveQuery
{
	public function behaviors() {
		return [
			[
				'class' => ClosureTableQuery::className(),
				'tableName' => 'category_tree'
			],
		];
	}
}
```

Migrations / Changing database schema
--------------------------
After configuring your model, you must copy migration file from behavior migrations folder 
to your project migrations folder.
Please read the comments in file, change migration as you need and run migration:

```sh
php yii migrate
```

Also you change database schema directly using an example from .sql dump file in schema folder.

Road map
--------------------------

~~~
1. Write documentation
2. Write tests
3. bug fixes
4. EVENT_DELETE
5. DeleteNode method refactoring
6. isAncestor() and isDescendant() methods
~~~

