<?php

namespace tigrov\tests\unit\pgsql\audit\data;

use tigrov\pgsql\audit\AuditableBehavior;

class AuditableModel extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'audit' => AuditableBehavior::className(),
        ];
    }
}