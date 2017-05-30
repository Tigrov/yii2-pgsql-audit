<?php
/**
 * @link https://github.com/tigrov/yii2-pgsql-audit
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\pgsql\audit\migrations;

use yii\db\Migration;
use tigrov\pgsql\audit\ModelClass;
use tigrov\pgsql\audit\AuditType;

/**
 * Migration to initialize the audit table
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class m170512_163340_init_audit extends Migration
{
    public function safeUp()
    {
        // First time create model_class enum for $identityClass only. Other model classes will be added as you go.
        $user = \Yii::$app->has('user') ? \Yii::$app->get('user', false) : null;
        $identityClass = $user ? $user->identityClass : '';
        ModelClass::create([$identityClass]);
        AuditType::create(['insert', 'update', 'delete']);

        $this->createTable('{{%audit}}', [
            'id' => 'bigpk',
            'model_class' => $this->db->quoteColumnName(ModelClass::typeName()) . ' NOT NULL',
            'pk_value' => $this->integer()->notNull(),
            'user_id' => $this->integer(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('now()'),
            'type_key' => $this->db->quoteColumnName(AuditType::typeName()) . ' NOT NULL',
            'old_values' => 'jsonb',
            'new_values' => 'jsonb',
        ]);

        $this->createIndex('audit_model_class_pk_value', '{{%audit}}', ['model_class', 'pk_value']);
        $this->createIndex('audit_user_id', '{{%audit}}', ['user_id']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%audit}}');

        ModelClass::drop();
        AuditType::drop();
    }
}