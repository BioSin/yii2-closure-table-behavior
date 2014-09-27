<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 * @copyright Copyright (c) 2014 Valentin Konusov
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @link https://github.com/BioSin/yii2-closure-table-behavior
 * @version 0.1.0
 */

namespace ValentineK\behaviors;

use yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;

class ClosureTableQuery extends Behavior
{

	/**
     * @var
     */
    public $closureTableName;

    /**
     * @var string
     */
    public $childAttribute = 'child';

    /**
     * @var string
     */
    public $parentAttribute = 'parent';

    /**
     * @var string
     */
    public $depthAttribute = 'depth';

    /**
     * @var string
     */
    public $isLeafParameter = 'leaf';

    /**
     * @var ActiveRecord the owner of this behavior.
     */
    public $owner;

	/**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if($this->closureTableName == null)
            throw new InvalidConfigException('Closure table name is not configured!');
    }

	/**
     * Find roots
     * @return yii\db\ActiveQuery
     */
    public function roots()
    {
        $query = $this->owner->find();
        $db = $this->owner->getDb();
        $primaryKeyName = $db->quoteColumnName($this->owner->primaryKey()[0]);
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $query->join('LEFT JOIN',
            $this->closureTableName.' as ct1',
            $primaryKeyName."=ct1.".$childAttribute);
        $query->join('LEFT JOIN',
            $this->closureTableName.' as ct2',
            'ct1.'.$childAttribute.'=ct2.'.$childAttribute
            .' AND ct2.'.$parentAttribute.' <> ct1.'.$parentAttribute);
        $query->andWhere('ct2.' . $parentAttribute.' IS NULL');

        return $query;
    }