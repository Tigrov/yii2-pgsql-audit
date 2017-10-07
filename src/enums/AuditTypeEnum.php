<?php
/**
 * @link https://github.com/tigrov/yii2-pgsql-audit
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\pgsql\audit\enums;

/**
 * Enum type to store audit type values
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class AuditTypeEnum extends \tigrov\pgsql\enum\EnumBehavior
{
    const INSERT = 'insert';
    const UPDATE = 'update';
    const DELETE = 'delete';
}