<?php
/**
 * @link https://github.com/tigrov/yii2-pgsql-audit
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\pgsql\audit\migrations;

use tigrov\pgsql\audit\Audit;
use tigrov\pgsql\audit\enums\RouteEnum;
use yii\db\Migration;
use tigrov\pgsql\audit\enums\ClassNameEnum;
use tigrov\pgsql\audit\enums\AuditTypeEnum;

/**
 * Migration to initialize the audit table
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class m170512_163340_init_audit extends Migration
{
    public function safeUp()
    {
        if (!ClassNameEnum::exists()) {
            ClassNameEnum::create([]);
        }
        if (!RouteEnum::exists()) {
            RouteEnum::create([]);
        }
        AuditTypeEnum::create();

        $this->createTable(Audit::tableName(), [
            'id' => 'bigpk',
            'model_class' => $this->db->quoteColumnName(ClassNameEnum::typeName()) . ' NOT NULL',
            'pk_value' => $this->integer()->notNull(),
            'user_id' => $this->integer(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('now()'),
            'route' => $this->db->quoteColumnName(RouteEnum::typeName()),
            'type_key' => $this->db->quoteColumnName(AuditTypeEnum::typeName()) . ' NOT NULL',
            'old_values' => 'jsonb',
            'new_values' => 'jsonb',
        ]);

        $this->createIndex('audit_model_class_pk_value', '{{%audit}}', ['model_class', 'pk_value']);
        $this->createIndex('audit_user_id', '{{%audit}}', ['user_id']);
    }

    public function safeDown()
    {
        $this->dropTable(Audit::tableName());

        ClassNameEnum::drop();
        RouteEnum::drop();
        AuditTypeEnum::drop();
    }
}