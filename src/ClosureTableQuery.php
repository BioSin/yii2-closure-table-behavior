<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 * @copyright Copyright (c) 2014 Valentin Konusov
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/BioSin/yii2-closure-table-behavior
 */

namespace valentinek\behaviors;

use yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;

class ClosureTableQuery extends Behavior
{

    /**
     * @var
     */
    public $tableName;

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

        if ($this->tableName == null)
            throw new InvalidConfigException('Closure table name is not configured!');
    }

    /**
     * Find roots
     * @return yii\db\ActiveQuery
     */
    public function roots()
    {
        $query = $this->owner;
        $modelClass = $query->modelClass;
        $db = $modelClass::getDb();
        $primaryKeyName = $db->quoteColumnName($modelClass::primaryKey()[0]);
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $query->leftJoin($this->tableName . ' as ct1',
            $primaryKeyName . "=ct1." . $childAttribute);
        $query->leftJoin($this->tableName . ' as ct2',
            'ct1.' . $childAttribute . '=ct2.' . $childAttribute
            . ' AND ct2.' . $parentAttribute . ' <> ct1.' . $parentAttribute);
        $query->andWhere('ct2.' . $parentAttribute . ' IS NULL');
        return $query;
    }

    /**
     * Find descendants
     * @param $primaryKey
     * @param int|null $depth the depth
     * @return yii\db\ActiveQuery
     */
    public function descendantsOf($primaryKey, $depth = null)
    {
        $query = $this->owner;
        $modelClass = $query->modelClass;
        $db = $modelClass::getDb();
        $primaryKeyName = $db->quoteColumnName($modelClass::primaryKey()[0]);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $depthAttribute = $db->quoteColumnName($this->depthAttribute);
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $query->join('INNER JOIN',
            $this->tableName.' as ct1',
            'ct1.'.$childAttribute . '=' . $primaryKeyName);
        $query->andWhere('ct1.'.$parentAttribute . '=' . $db->quoteValue($primaryKey));

        if ($depth === null) {
            $query->andWhere('ct1.' . $childAttribute . '!=' . 'ct1.' . $parentAttribute);
        } else {
            $query->andWhere(['between', 'ct1.'.$depthAttribute, 1, intval($depth)]);
        }

        return $query;
    }

    /**
     * Named scope. Gets children for node (direct descendants only).
     * @param int|string $primaryKey
     * @return yii\db\ActiveQuery
     */
    public function childrenOf($primaryKey)
    {
        return $this->descendantsOf($primaryKey, 1);
    }

    /**
     * Named scope. Gets ancestors for node.
     * @param $primaryKey
     * @param int|null $depth
     * @param null|bool $reverseDirection null if no order
     * @return yii\db\ActiveQuery
     */
    public function ancestorsOf($primaryKey, $depth = null, $reverseDirection = null)
    {
        $query = $this->owner;
        $modelClass = $query->modelClass;
        $db = $modelClass::getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $depthAttribute = $db->quoteColumnName($this->depthAttribute);

        if($reverseDirection !== null)
            $query = $this->pathOf($primaryKey, $query, 'ctp', $reverseDirection);
        else
            $query = $this->unorderedPathOf($primaryKey, $query, 'ctp');

        if ($depth === null) {
            $query->andWhere('ctp.' . $childAttribute . '!=' . 'ctp.' . $parentAttribute);
        } else {
            $query->andWhere(['between', 'ctp.'.$depthAttribute, 1, intval($depth)]);
        }

        return $query;
    }

    /**
     * Gets path to the node.
     * @param $primaryKey
     * @return yii\db\ActiveQuery
     */
    public function unorderedPathOf($primaryKey, $query = null, $tableAlias = 'ctp')
    {
        if(!$query instanceof yii\db\ActiveQuery)
            $query = $this->owner;

        $modelClass = $query->modelClass;
        $db = $modelClass::getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $primaryKeyName = $db->quoteColumnName($modelClass::primaryKey()[0]);
        $query->innerJoin($this->tableName.' as ' . $tableAlias,
            $tableAlias . '.' . $parentAttribute . '=' . $primaryKeyName);
        $query->andWhere($tableAlias . '.' . $childAttribute . '=' . $db->quoteValue($primaryKey));

        if ($query->select === null) {
            $query->addSelect('*');
            $query->addSelect($tableAlias . "." . $this->depthAttribute);
        }

        return $query;
    }

    /**
     * Named scope. Gets path to the node.
     * @param int|string $primaryKey
     * @return yii\db\ActiveQuery
     */
    public function pathOf($primaryKey, $query = null, $tableAlias = 'ctp', $reverseDirection = false)
    {
        if(!$query instanceof yii\db\ActiveQuery)
            $query = $this->owner;

        $modelClass = $query->modelClass;
        $db = $modelClass::getDb();
        $query = $this->unorderedPathOf($primaryKey, $query, $tableAlias);
        $query->addOrderBy($tableAlias.'.' . $db->quoteColumnName($this->depthAttribute) . ' '.($reverseDirection ? 'ASC' : 'DESC'));

        return $query;
    }

    /**
     * Named scope. Gets parent of node.
     * @param int|string $primaryKey
     * @return yii\db\ActiveQuery
     */
    public function parentOf($primaryKey)
    {
        return $this->ancestorsOf($primaryKey, 1);
    }

    /**
     * Named scope. Get path with its children.
     * Warning: root node isn't returned.
     *
     * @param int|string $primaryKey
     * @return mixed
     */
    public function fullPathOf($primaryKey)
    {
        $query = $this->owner;
        $modelClass = $query->modelClass;
        $db = $modelClass::getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $depthAttribute = $db->quoteColumnName($this->depthAttribute);
        $primaryKeyName = $db->quoteColumnName($modelClass::primaryKey()[0]);
        $query->innerJoin($this->tableName . ' as ct1');
        $query->innerJoin($this->tableName . ' as ct2',
            'ct1.' . $parentAttribute .' = ct2.' . $parentAttribute
            . ' AND ' . $primaryKeyName . ' = ct2.' . $childAttribute
            . ' AND ct2.' . $depthAttribute . ' = 1'
        );
        $query->andWhere('ct1.' . $childAttribute . '=' . $db->quoteValue($primaryKey));

        return $query;
    }

    /**
     * Named scope. Selects leaf column which indicates if record is a leaf
     * @return yii\db\ActiveQuery
     */
    public function leaf()
    {
        $query = $this->owner;
        $modelClass = $query->modelClass;
        $db = $modelClass::getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $primaryKeyName = $db->quoteColumnName($modelClass::primaryKey()[0]);

        if ($query->select === null) {
            $query->addSelect('*');
            $query->addSelect("ISNULL(ctleaf." . $parentAttribute . ") AS " . $this->isLeafParameter);
        }

        $query->leftJoin($this->tableName . ' as ctleaf',
            'ctleaf.' . $parentAttribute . '=' . $primaryKeyName
            . ' AND ctleaf.' . $parentAttribute . '!= ctleaf.' . $childAttribute
        );

        $query->addGroupBy($primaryKeyName);

        return $query;
    }

    /**
     * Move node
     * @param ActiveRecord|int|string $target
     * @param ActiveRecord|int|string $node if null, owner id will be used
     * @throws \Exception
     * @throws yii\db\Exception
     */
    public function moveTo($target, $node = null)
    {
        $query = $this->owner;
        $modelClass = $query->modelClass;
        $db = $modelClass::getDb();

        $targetId = ($target instanceof ActiveRecord)
            ? $target->primaryKey
            : $target;

        if($node === null)
            $node = $this->owner;

        $nodeId = ($node instanceof ActiveRecord)
            ? $node->primaryKey
            : $node;

        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $depthAttribute = $db->quoteColumnName($this->depthAttribute);
        $tableName = $db->quoteTableName($this->tableName);

        $transaction = $db->beginTransaction();
        try {
            $sql = "DELETE ct1 FROM ".$tableName." ct1 "
                . " INNER JOIN ".$tableName." ct2 ON ct1.".$childAttribute." = ct2.".$childAttribute
                . " LEFT JOIN ".$tableName." ct3 ON ct3.".$parentAttribute." = ct2.".$parentAttribute
                . " AND ct3.".$childAttribute." = ct1.".$parentAttribute
                ." WHERE ct2.".$parentAttribute." = :nodeId AND ct3.".$parentAttribute." IS NULL";

            if(!$db->createCommand($sql)->bindValue(':nodeId', $nodeId)->execute()) {
                throw new \Exception('Node had no records in closure table', 200);
            }

            $sql = "INSERT INTO " . $tableName . " (" . $parentAttribute . "," . $childAttribute . "," . $depthAttribute . ")"
                . " SELECT ct1." . $parentAttribute . ", ct2." . $childAttribute
                . " , ct1." . $depthAttribute . " + ct2." . $depthAttribute. "+1 "
                . " FROM " . $tableName . " ct1 INNER JOIN " . $tableName . " ct2 "
                . " WHERE ct2." . $parentAttribute . " = :nodeId AND ct1." . $childAttribute . " = :targetId";

            if(!$db->createCommand($sql)->bindValues([':nodeId'=>$nodeId, ':targetId'=>$targetId])->execute()) {
                throw new \Exception("Target node does not exist", 201);
            }

            $transaction->commit();

        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes node and it's descendants.
     * @param int|string|null $primaryKey if null, owner id will be used
     * @return int number of rows deleted
     * @throws yii\db\Exception
     */
    public function deleteNode($primaryKey)
    {
        $modelClass = $this->owner->modelClass;
        $db = $modelClass::getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $tableName = $db->quoteTableName($this->tableName);
        $primaryKeyName = $db->quoteColumnName($modelClass::primaryKey()[0]);

        $sql = "DELETE ct1, t FROM " . $tableName . " ct1 "
            . " INNER JOIN " . $tableName . " ct2 ON ct1." . $childAttribute . "= ct2." . $childAttribute
            . " INNER JOIN " . $modelClass::tableName() . " t ON ct1." . $childAttribute . "= t." . $primaryKeyName
            . " WHERE ct2." . $parentAttribute . "= :pk";

        return $db->createCommand($sql)->bindValue(':pk', $primaryKey)->execute();
    }

    /**
     * Insert closure table records
     * @param $primaryKey
     * @return int
     * @throws \Exception
     */
    public function markAsRoot($primaryKey)
    {
        $modelClass = $this->owner->modelClass;
        $db = $modelClass::getDb();

        return $db->createCommand()->insert($this->tableName, [
            $this->parentAttribute => $primaryKey,
            $this->childAttribute => $primaryKey,
            $this->depthAttribute => 0
        ])->execute();
    }
}