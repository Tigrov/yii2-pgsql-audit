<?php

namespace tigrov\tests\unit\pgsql\audit;

use tigrov\pgsql\audit\Audit;
use tigrov\tests\unit\pgsql\audit\data\AuditableModel;
use yii\db\Expression;

class AuditTest extends TestCase
{
    public function testGetModel()
    {
        $model = new AuditableModel;
        $model->name = 'name';
        $model->value = 1;
        $model->agentUserId = 10;
        $this->assertTrue($model->save(false));

        $audit = $model->lastAudit;
        $this->assertEquals($model->getAttributes(null, ['created_at']), $audit->model->getAttributes(null, ['created_at']));

        return $model;
    }

    public function testFindByModel()
    {
        $model = new AuditableModel;
        $total = 10;
        for ($i = 0; $i < $total; ++$i) {
            $model->name = 'name' . $i;
            $model->value = $i;
            $this->assertTrue($model->save(false));
        }

        $audits = Audit::findByModel($model)->all();

        $this->assertSame($total, count($audits));

        foreach ($audits as $audit) {
            $this->assertSame($model::className(), $audit->model_class);
            $this->assertSame($model->primaryKey, $audit->pk_value);
        }
    }

    public function testFindByUserId()
    {
        $userId = 99;
        $total = 10;
        for ($i = 0; $i < $total; ++$i) {
            $model = new AuditableModel;
            $model->name = 'name' . $i;
            $model->value = $i;
            $model->agentUserId = $userId;
            $this->assertTrue($model->save(false));
        }

        $audits = Audit::findByUserId($userId)->all();

        $this->assertSame($total, count($audits));

        foreach ($audits as $audit) {
            $this->assertSame($userId, $audit->user_id);
        }
    }

    public function testRevert()
    {
        $model = new AuditableModel;
        $total = 10;
        for ($i = 0; $i < $total; ++$i) {
            $model->name = 'name' . $i;
            $model->value = $i;
            $this->assertTrue($model->save(false));
        }

        $this->assertSame('name9', $model->name);
        $this->assertSame(9, $model->value);

        $modelId = $model->id;
        $this->assertSame(1, $model->delete());

        $model = new AuditableModel(['id' => $modelId]);
        $model = $model->lastAudit->revert();
        $this->assertSame('name9', $model->name);
        $this->assertSame(9, $model->value);

        $revertToValue = 3;
        $audit = Audit::findByModel($model)
            ->where(new Expression("jsonb_extract_path_text(old_values, 'value')::integer = :value", ['value' => $revertToValue]))
            ->one();
        $model = $audit->revert();

        $this->assertSame('name' . $revertToValue, $model->name);
        $this->assertSame($revertToValue, $model->value);

        $model = $model->firstAudit->revert();
        $this->assertSame('name0', $model->name);
        $this->assertSame(0, $model->value);
    }
}