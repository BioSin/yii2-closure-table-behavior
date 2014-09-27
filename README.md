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
	public function behaviors() {
		return [
			[
				'class' => ClosureTable::className(),
				'closureTableName' => 'category_tree'
			],
		];
	}

	public static function find()
	{
		return new CategoryQuery(get_called_class());
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
				'closureTableName' => 'category_tree'
			],
		];
	}
}
```

Road map
--------------------------

~~~
1. Write documentation
2. Write tests
3. bug fixes
~~~

