<?php
/**
 * @link https://github.com/tigrov/yii2-pgsql-audit
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\pgsql\audit;

use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\web\IdentityInterface;

/**
 * Behavior to audit an ActiveRecord model
 *
 * @property integer $agentUserId
 * @property \DateTime $createdAt
 * @property integer $createdBy
 * @property \DateTime $updatedAt
 * @property integer $updatedBy
 * @property Audit $firstAudit
 * @property Audit $lastAudit
 * @property ActiveQuery $auditQuery
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class AuditableBehavior extends Behavior
{
    /** @var array attributes to be excepted from audit */
    public $exceptAttributes = ['created_at', 'created_by', 'updated_at', 'updated_by'];

    /** @var int */
    private $_userId;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Set user ID as an agent of audits
     *
     * @param int $userId
     */
    public function setAgentUserId($userId)
    {
        $this->_userId = $userId;
    }

    /**
     * Get the agent user ID for audits
     *
     * @return int|null
     */
    public function getAgentUserId()
    {
        return $this->_userId !== null
            ? $this->_userId
            : (\Yii::$app->has('user')
                ? (\Yii::$app->getUser()->getId()
                    ?: ($this->owner instanceof IdentityInterface
                        ? $this->owner->getId()
                        : null))
                : null);
    }

    /**
     * Get non-auditable attributes include primary keys
     *
     * @return string[]
     */
    public function getNonAuditableAttributes()
    {
        return array_merge($this->exceptAttributes, $this->owner->primaryKey());
    }

    /**
     * Filter auditable values
     *
     * @param $values
     * @return array only auditable values
     */
    public function filterAuditableValues($values)
    {
        return $values
            ? array_diff_key($values, array_flip($this->getNonAuditableAttributes()))
            : $values;
    }

    /**
     * Get date and time when the object was created
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->getTimestamp($this->firstAudit);
    }

    /**
     * Get the User who created the model first time
     *
     * @return IdentityInterface|null
     */
    public function getCreatedBy()
    {
        return $this->getUser($this->firstAudit);
    }

    /**
     * Get date and time when the model was updated
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->getTimestamp($this->lastAudit);
    }

    /**
     * Get the User who updated the model
     *
     * @return IdentityInterface|null
     */
    public function getUpdatedBy()
    {
        return $this->getUser($this->lastAudit);
    }

    /**
     * Get first audit for the model
     *
     * @return Audit
     */
    public function getFirstAudit()
    {
        $query = Audit::findByModel($this->owner);
        return $query->orderBy(['id' => SORT_ASC])->limit(1)->one();
    }

    /**
     * Get last audit for the model
     *
     * @return Audit
     */
    public function getLastAudit()
    {
        $query = Audit::findByModel($this->owner);
        return $query->orderBy(['id' => SORT_DESC])->limit(1)->one();
    }

    /**
     * Get date and time of an audit
     *
     * @param Audit $audit
     * @return \DateTime|null
     */
    protected function getTimestamp(Audit $audit)
    {
        return $audit ? $audit->created_at : null;
    }

    /**
     * Get an User of an audit
     *
     * @param Audit $audit
     * @return IdentityInterface|null
     */
    protected function getUser(Audit $audit)
    {
        return $audit ? $audit->getUser() : null;
    }

    /**
     * Event after insert
     *
     * @param AfterSaveEvent $event
     */
    public function afterInsert(AfterSaveEvent $event)
    {
        $this->afterSave('insert', $event);
    }

    /**
     * Event after update
     *
     * @param AfterSaveEvent $event
     */
    public function afterUpdate(AfterSaveEvent $event)
    {
        $this->afterSave('update', $event);
    }

    /**
     * @param string $typeKey type of audit
     * @param AfterSaveEvent $event
     */
    protected function afterSave($typeKey, AfterSaveEvent $event)
    {
        if ($oldValues = $this->filterAuditableValues($event->changedAttributes)) {
            /** @var ActiveRecord $model */
            $model = $this->owner;
            $newValues = $model->getAttributes(array_keys($oldValues));
            $this->insert($typeKey, $typeKey == 'insert' ? null : $oldValues, $newValues);
        }
    }

    /**
     * Event after delete
     *
     * @param Event $event
     */
    public function afterDelete(Event $event)
    {
        /** @var ActiveRecord $model */
        $model = $this->owner;
        $this->insert('delete', $model->getAttributes(null, $this->getNonAuditableAttributes()));
    }

    /**
     * Insert values in audit table
     *
     * @param string $typeKey type of audit
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return bool
     */
    protected function insert($typeKey, $oldValues, $newValues = null)
    {
        /** @var ActiveRecord $model */
        $model = $this->owner;

        return (new Audit([
            'model_class' => $model::className(),
            'pk_value' => $model->getPrimaryKey(),
            'user_id' => $this->getAgentUserId(),
            'type_key' => $typeKey,
            'old_values' => $this->dbTypecast($oldValues),
            'new_values' => $this->dbTypecast($newValues),
        ]))->save(false);
    }

    /**
     * Converts the input value according to [[type]] and [[dbType]] for use in a db query.
     *
     * @param array $attributes to be converted
     * @return array
     */
    protected function dbTypecast($attributes)
    {
        if (!$attributes) {
            return $attributes;
        }

        /** @var ActiveRecord $model */
        $model = $this->owner;

        $list = [];
        $columns = $model::getTableSchema()->columns;
        foreach ($attributes as $attribute => $value) {
            $list[$attribute] = isset($columns[$attribute])
                ? $columns[$attribute]->dbTypecast($value)
                : $value;
        }

        return $list;
    }
}