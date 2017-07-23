<?php

namespace tigrov\tests\unit\pgsql\audit;

use tigrov\tests\unit\pgsql\audit\data\AuditableModel;

class AuditableBehaviorTest extends TestCase
{
    public function testGetNonAuditableAttributes()
    {
        $model = new AuditableModel;

        $this->assertSame(['created_at', 'created_by', 'updated_at', 'updated_by', 'id'], $model->getNonAuditableAttributes());
    }

    /**
     * @dataProvider valuesProvider
     */
    public function testFilterAuditableValues($name, $value)
    {
        $model = new AuditableModel;
        $model->name = $name;
        $model->value = $value;
        $this->assertTrue($model->save(false));

        $this->assertSame(['name' => $name, 'value' => $value], $model->filterAuditableValues($model->getAttributes()));
    }


    public function testAfterInsert()
    {
        $name = 'test';
        $value = 1;

        $model = new AuditableModel;
        $model->name = $name;
        $model->value = $value;
        $this->assertTrue($model->save(false));

        $expected = [
            'model_class' => AuditableModel::className(),
            'pk_value' => $model->getPrimaryKey(),
            'user_id' => \Yii::$app->has('user') ? \Yii::$app->getUser()->getId() : null,
            'route' => 'test/index',
            'type_key' => 'insert',
            'old_values' => null,
            'new_values' => [
                'name' => $name,
                'value' => $value,
            ],
        ];
        $this->assertSame($expected, $model->firstAudit->getAttributes(null, ['id', 'created_at']));
        $this->assertSame($expected, $model->lastAudit->getAttributes(null, ['id', 'created_at']));

        $now = new \DateTime;
        $this->assertLessThanOrEqual(1, static::convertIntervalToSeconds($now->diff($model->createdAt)));

        return $model;
    }

    /**
     * @depends testAfterInsert
     */
    public function testAfterUpdate($model)
    {
        $createdBy = \Yii::$app->has('user') ? \Yii::$app->getUser()->getId() : null;
        $updatedBy = 10;

        $firstExpected = [
            'model_class' => AuditableModel::className(),
            'pk_value' => $model->getPrimaryKey(),
            'user_id' => $createdBy,
            'route' => 'test/index',
            'type_key' => 'insert',
            'old_values' => null,
            'new_values' => [
                'name' => $model->name,
                'value' => $model->value,
            ],
        ];

        $model->name = 'new value';
        $model->setAgentUserId($updatedBy);
        $this->assertTrue($model->save(false));
        $lastExpected = [
            'model_class' => AuditableModel::className(),
            'pk_value' => $model->getPrimaryKey(),
            'user_id' => $updatedBy,
            'route' => 'test/index',
            'type_key' => 'update',
            'old_values' => [
                'name' => $firstExpected['new_values']['name'],
            ],
            'new_values' => [
                'name' => $model->name,
            ],
        ];

        $this->assertSame($firstExpected, $model->firstAudit->getAttributes(null, ['id', 'created_at']));
        $this->assertSame($lastExpected, $model->lastAudit->getAttributes(null, ['id', 'created_at']));

        $this->assertSame(\Yii::$app->has('user') ? \Yii::$app->getUser()->getIdentity() : null, $model->createdBy);

        $identityClass = \Yii::$app->has('user') ? \Yii::$app->getUser()->identityClass : null;
        $this->assertSame(\Yii::$app->has('user') ? $identityClass::findIdentity($updatedBy) : null, $model->updatedBy);

        $now = new \DateTime;
        $this->assertLessThanOrEqual(1, static::convertIntervalToSeconds($now->diff($model->updatedAt)));

        return $model;
    }

    /**
     * @depends testAfterUpdate
     */
    public function testAfterDelete($model)
    {
        $expected = [
            'model_class' => AuditableModel::className(),
            'pk_value' => $model->getPrimaryKey(),
            'user_id' => $model->agentUserId,
            'route' => 'test/index',
            'type_key' => 'delete',
            'old_values' => [
                'name' => $model->name,
                'value' => $model->value,
            ],
            'new_values' => null,
        ];

        $this->assertSame(1, $model->delete());
        $this->assertSame($expected, $model->lastAudit->getAttributes(null, ['id', 'created_at']));
    }

    protected static function convertIntervalToSeconds($interval)
    {
        return (new \DateTime)->add($interval)->getTimestamp() - (new \DateTime)->getTimestamp();
    }

    public function valuesProvider()
    {
        return [
            ['string', 1],
            ['more values', 2],
        ];
    }
}