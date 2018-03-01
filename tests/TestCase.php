<?php

namespace tigrov\tests\unit\pgsql\audit;

use yii\di\Container;
use yii\helpers\ArrayHelper;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    const TABLE_NAME = 'auditable_model';

    private static $isMigrated = false;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $config = require(__DIR__ . '/data/config.php');
        if (file_exists(__DIR__ . '/data/config.local.php')) {
            $config = ArrayHelper::merge($config, require(__DIR__ . '/data/config.local.php'));
        }

        $this->mockApplication($config);

        if (!self::$isMigrated) {
            $migrateController = new \yii\console\controllers\MigrateController('migrate', \Yii::$app);
            $migrateController->migrationPath = null;
            $migrateController->migrationNamespaces[] = 'tigrov\pgsql\audit\migrations';
            try {
                $migrateController->runAction('down', ['interactive' => 0]);
            } catch (\Exception $e) {}
            $migrateController->runAction('up', ['interactive' => 0]);

            try {
                $this->dropTables();
            } catch (\Exception $e) {}
            $this->createTables();

            self::$isMigrated = true;
        }
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        $_SERVER['argv'] = ['yii', 'test/index'];

        $app = new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'controllerMap' => [
                'test' => TestConsoleController::class,
            ],
        ], $config));

        $app->handleRequest($app->getRequest());
    }

    protected function mockWebApplication($config = [], $appClass = '\yii\web\Application')
    {
        $_GET['r'] = 'test/index';

        $app = new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'SDefdsfqdxjfwF8s9oqwefJD',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
            'controllerMap' => [
                'test' => TestWebController::class,
            ],
        ], $config));

        $app->handleRequest($app->getRequest());
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        \Yii::$app = null;
        \Yii::$container = new Container();
    }

    /**
     * Setup table for test ActiveRecord
     */
    protected function createTables()
    {
        $db = \Yii::$app->getDb();

        $columns = [
            'id' => 'pk',
            'name' => 'varchar',
            'value' => 'integer',
            'created_at' => 'timestamp DEFAULT now()',
        ];

        if (null === $db->schema->getTableSchema(static::TABLE_NAME)) {
            $db->createCommand()->createTable(static::TABLE_NAME, $columns)->execute();
            $db->getSchema()->refreshTableSchema(static::TABLE_NAME);
        }
    }

    protected function dropTables()
    {
        \Yii::$app->getDb()->createCommand()->dropTable(static::TABLE_NAME)->execute();
    }
}