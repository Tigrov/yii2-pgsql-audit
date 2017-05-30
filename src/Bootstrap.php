<?php
/**
 * @link https://github.com/tigrov/yii2-pgsql-audit
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\pgsql\audit;

/**
 * Bootstrap class
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class Bootstrap implements \yii\base\BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap['migrate']['migrationNamespaces'][] = 'tigrov\pgsql\audit\migrations';
        }
    }
}