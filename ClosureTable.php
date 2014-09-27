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

class ClosureTable extends Behavior
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
     * Find descendants
     * @param $primaryKey
     * @param int|null $depth the depth
     * @return yii\db\ActiveQuery
     */
    public function descendantsOf($primaryKey, $depth = null)
    {
        $query = $this->owner->find();
        $db = $this->owner->getDb();
        $primaryKeyName = $db->quoteColumnName($this->owner->primaryKey()[0]);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $depthAttribute = $db->quoteColumnName($this->depthAttribute);
        $query->join('INNER JOIN',
            $this->closureTableName.' as ct1',
            'ct1.'.$childAttribute = $primaryKeyName);
        $query->andWhere('ct1.'.$parentAttribute . '=' . $db->quoteValue($primaryKey));
        $query->addSelect('ct1.' . $depthAttribute);

        if ($depth === null) {
            $query->andWhere('ct1.' . $childAttribute . '!=' . 'ct1.' . $parentAttribute);
        } else {
            $query->andWhere(['between', 'ct1.'.$depthAttribute, 1, intval($depth)]);
        }

        return $query;
    }

    /**
     * Named scope. Gets descendants for node.
     * @param int|null $depth
     * @return yii\db\ActiveQuery
     */
    public function descendants($depth = null)
    {
        return $this->descendantsOf($this->owner->primaryKey, $depth);
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
     * Named scope. Gets children for node (direct descendants only).
     * @return yii\db\ActiveQuery
     */
    public function children()
    {
        return $this->descendants(1);
    }

    /**
     * Named scope. Gets ancestors for node.
     * @param $primaryKey
     * @param int|null $depth
     * @return yii\db\ActiveQuery
     */
    public function ancestorsOf($primaryKey, $depth = null)
    {
        $query = $this->owner->find();
        $db = $this->owner->getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $depthAttribute = $db->quoteColumnName($this->depthAttribute);
        $this->unorderedPathOf($primaryKey);
        if ($depth === null) {
            $query->andWhere('ctp.' . $childAttribute . '!=' . 'ctp.' . $parentAttribute);
        } else {
            $query->andWhere(['between', 'ctp.'.$depthAttribute, 1, intval($depth)]);
        }

        return $query;
    }

    /**
     * Named scope. Gets ancestors for node.
     * @param int|null $depth
     * @return yii\db\ActiveQuery
     */
    public function ancestors($depth = null)
    {
        return $this->ancestorsOf($this->owner->primaryKey, $depth);
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
     * Named scope. Gets parent of node.
     * @return yii\db\ActiveQuery
     */
    public function parent()
    {
        return $this->ancestors(1);
    }

    /**
     * Named scope. Gets path to the node.
     * @param $primaryKey
     * @return yii\db\ActiveQuery
     */
    public function unorderedPathOf($primaryKey)
    {
        $query = $this->owner->find();
        $db = $this->owner->getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $primaryKeyName = $db->quoteColumnName($this->owner->primaryKey()[0]);
        $query->join('INNER JOIN',
            $this->closureTableName.' as ctp',
            'ctp.' . $parentAttribute . '=' . $primaryKeyName);
        $query->andWhere('ctp' . $childAttribute . '=' . $db->quoteValue($primaryKey));

        return $query;
    }

    /**
     * Named scope. Gets path to the node.
     * @param int|string $primaryKey
     * @return yii\db\ActiveQuery
     */
    public function pathOf($primaryKey)
    {
        $query = $this->owner->find();
        $db = $this->owner->getDb();
        $this->unorderedPathOf($primaryKey);
        $query->addOrderBy('ctp.' . $db->quoteColumnName($this->depthAttribute) . ' DESC');

        return $query;
    }

    /**
     * Named scope. Gets path to the node.
     * @return yii\db\ActiveQuery
     */
    public function path()
    {
        return $this->pathOf($this->owner->primaryKey);
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
        $query = $this->owner->find();
        $db = $this->owner->getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $depthAttribute = $db->quoteColumnName($this->depthAttribute);
        $primaryKeyName = $db->quoteColumnName($this->owner->primaryKey()[0]);
        $query->join('INNER JOIN',
            $this->closureTableName . ' as ct1');
        $query->join('INNER JOIN',
            $this->closureTableName . ' as ct2',
            'ct1.' . $parentAttribute .' = ct2.' . $parentAttribute
            . ' AND ' . $primaryKeyName . ' = ct2.' . $childAttribute
            . ' AND ct2.' . $depthAttribute . ' = 1'
        );
        $query->andWhere('ct1.' . $childAttribute . '=' . $db->quoteValue($primaryKey));

        return $query;
    }

    /**
     * Named scope. Get path with its children.
     * Warning: root node isn't returned.
     *
     * @return ActiveRecord the owner
     */
    public function fullPath()
    {
        return $this->fullPathOf($this->owner->primaryKey);
    }

    /**
     * Named scope. Selects leaf column which indicates if record is a leaf
     * @return yii\db\ActiveQuery
     */
    public function leaf()
    {
        $query = $this->owner->find();
        $db = $this->owner->getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $primaryKeyName = $db->quoteColumnName($this->owner->primaryKey()[0]);

        if ($query->select === null) {
            $query->addSelect("ISNULL(ctleaf." . $parentAttribute . ") AS " . $this->isLeafParameter);
        }

        $query->join('LEFT JOIN',
            $this->closureTableName . ' as ctleaf',
            'ctleaf.' . $parentAttribute . '=' . $primaryKeyName
            . ' AND ctleaf.' . $parentAttribute . '!= ctleaf.' . $childAttribute
        );

        $query->addGroupBy($primaryKeyName);

        return $query;
    }

    /**
     * leaf scope is required
     * @return bool
     */
    public function isLeaf()
    {
        return (boolean) $this->owner->{$this->isLeafParameter};
    }

    /**
     * Save node and insert closure table records with transaction
     * @param boolean $runValidation whether to perform validation before saving the record.
     * If the validation fails, the record will not be saved to database.
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @throws \Exception
     * @return boolean whether the saving succeeds
     */
    public function saveNodeAsRoot($runValidation = true, $attributes = null)
    {
        $db = $this->owner->getDb();

        $transaction = $db->beginTransaction();
        try {
            if (!$this->owner->save($runValidation, $attributes)) {
                $transaction->rollBack();
                return false;
            }
            $this->markAsRoot($this->owner->primaryKey);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Insert closure table records
     * @param $primaryKey
     * @return int
     * @throws yii\db\Exception
     */
    public function markAsRoot($primaryKey)
    {
        $db = $this->owner->getDb();

        return $db->createCommand()->insert($this->closureTableName, [
            $this->parentAttribute => $primaryKey,
            $this->childAttribute => $primaryKey,
            $this->depthAttribute => 0
        ])->execute();
    }

    /**
     * Appends node to target as child (Only for new records).
     * @param ActiveRecord|int|string $target
     * @param ActiveRecord|int|string $node
     * @return int number of rows inserted, on fail - 0
     * @throws yii\db\Exception
     */
    public function appendTo($target, $node = null)
    {
        $db = $this->owner->getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $depthAttribute = $db->quoteColumnName($this->depthAttribute);
        $closureTableName = $db->quoteTableName($this->closureTableName);

        $primaryKey = ($target instanceof ActiveRecord)
            ? $target->primaryKey
            : $target;

        if ($node === null)
            $node = $this->owner;

        $nodeId = ($node instanceof ActiveRecord)
            ? $node->primaryKey
            : $node;

        $cmd = $db->createCommand(
            'INSERT INTO ' . $closureTableName . ' '
            . '('.$parentAttribute.','.$childAttribute.','.$depthAttribute.') '
            . 'SELECT ' . $parentAttribute .', :nodeId' . ', ' . $depthAttribute . '+1 '
            . 'FROM ' . $closureTableName . ' WHERE ' . $childAttribute . '= :pk'
            . 'UNION ALL SELECT :nodeId, :nodeId, \'0\''
        );

        return $cmd->bindValues([':nodeId' => $nodeId, ':pk' => $primaryKey])->execute();
    }

    /**
     * Appends target to node as child.
     * @param ActiveRecord $target the target
     * @return boolean whether the appending succeeds.
     */
    public function append(ActiveRecord $target)
    {
        return $target->appendTo($this->owner);
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
        $db = $this->owner->getDb();

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
        $closureTableName = $db->quoteTableName($this->closureTableName);

        $transaction = $db->beginTransaction();
        try {
            $sql = "DELETE ct1 FROM ".$closureTableName." ct1 "
                . "INNER JOIN ".$closureTableName." ct2 ON ct1.".$childAttribute." = ct2.".$childAttribute
                . "LEFT JOIN ".$closureTableName." ct3 ON ct3.".$parentAttribute." = ct2.".$parentAttribute
                . "AND ct3.".$childAttribute." = ct1.".$parentAttribute
                ." WHERE ct2.".$parentAttribute." = :nodeId AND ct3.".$parentAttribute." IS NULL";

            if(!$db->createCommand($sql)->bindValue(':nodeId', $nodeId)->execute()) {
                throw new \Exception('Node had no records in closure table', 200);
            }

            $sql = "INSERT INTO " . $closureTableName . " (" . $parentAttribute . "," . $childAttribute . "," . $depthAttribute . ")"
                . "SELECT ct1." . $parentAttribute . ", ct2." . $childAttribute
                . ", ct1." . $depthAttribute . " + ct2." . $depthAttribute. "+1 "
                . "FROM " . $closureTableName . " ct1 INNER JOIN " . $closureTableName . " ct2 "
                . "WHERE ct2." . $parentAttribute . " = :nodeId AND ct1." . $childAttribute . " = :targetId";

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
    public function deleteNode($primaryKey = null)
    {
        $db = $this->owner->getDb();
        $childAttribute = $db->quoteColumnName($this->childAttribute);
        $parentAttribute = $db->quoteColumnName($this->parentAttribute);
        $closureTableName = $db->quoteTableName($this->closureTableName);
        $primaryKeyName = $db->quoteColumnName($this->owner->primaryKey()[0]);
        if ($primaryKey === null) {
            $primaryKey = $this->owner->primaryKey;
        }

        $sql = "DELETE ct1, t FROM " . $closureTableName . " ct1 "
            . " INNER JOIN " . $closureTableName . " ct2 ON ct1." . $childAttribute . "= ct2." . $childAttribute
            . " INNER JOIN " . $this->owner->tableName() . " t ON ct1." . $childAttribute . "= t." . $primaryKeyName
            . " WHERE ct2." . $parentAttribute . "= :pk";

        return $db->createCommand($sql)->bindValue(':pk', $primaryKey)->execute();
    }
}