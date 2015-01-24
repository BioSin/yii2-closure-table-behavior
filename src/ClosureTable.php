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

class ClosureTable extends Behavior
{
    /**
     * @var closure table name
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

        if($this->tableName == null)
            throw new InvalidConfigException('Closure table name is not configured!');
    }

    /**
     * Named scope. Gets descendants for node.
     * @param int|null $depth
     * @return yii\db\ActiveQuery
     */
    public function descendants($depth = null)
    {
        $modelClass = $this->owner;
        return $modelClass::find()->descendantsOf($this->owner->primaryKey, $depth);

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
     * @param int|null $depth
     * @param null|bool $reverseDirection null if no order
     * @return yii\db\ActiveQuery
     */
    public function ancestors($depth = null, $reverseDirection = null)
    {
        $modelClass = $this->owner;
        return $modelClass::find()->ancestorsOf($this->owner->primaryKey, $depth, $reverseDirection);
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
     * @return yii\db\ActiveQuery
     */
    public function path()
    {
        $modelClass = $this->owner;
        return $modelClass::find()->pathOf($this->owner->primaryKey);
    }

    /**
     * Named scope. Get path with its children.
     * Warning: root node isn't returned.
     *
     * @return ActiveRecord the owner
     */
    public function fullPath()
    {
        $modelClass = $this->owner;
        return $modelClass::find()->fullPathOf($this->owner->primaryKey);
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
     * Mark current record as root
     * @return mixed
     */
    public function markAsRoot()
    {
        $owner = $this->owner;
        return $owner::find()->markAsRoot($owner->primaryKey);
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
        $tableName = $db->quoteTableName($this->tableName);

        $primaryKey = ($target instanceof ActiveRecord)
            ? $target->primaryKey
            : $target;

        if ($node === null)
            $node = $this->owner;

        $nodeId = ($node instanceof ActiveRecord)
            ? $node->primaryKey
            : $node;

        $cmd = $db->createCommand(
            'INSERT INTO ' . $tableName . ' '
            . '('.$parentAttribute.','.$childAttribute.','.$depthAttribute.') '
            . 'SELECT ' . $parentAttribute .', :nodeId' . ', ' . $depthAttribute . '+1 '
            . 'FROM ' . $tableName . ' WHERE ' . $childAttribute . '= :pk '
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
     * Deletes node and it's descendants.
     * @return int number of rows deleted
     * @throws yii\db\Exception
     */
    public function deleteNode()
    {
        $modelClass = $this->owner;
        return $modelClass::find()->deleteNode($this->owner->primaryKey);
    }

    public function moveTo($target, $node = null)
    {
        $modelClass = $this->owner;
        return $modelClass::find()->moveTo($target, $this->owner->primaryKey);
    }
}