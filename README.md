yii2-pgsql-audit
==============

Audit for ActiveRecord models for Yii2, for PostgreSQL only.

The extension is used as a behavior for an ActiveRecord model. It will store all changes for the model and gives you possibility to review the changes and revert them. 

[![Latest Stable Version](https://poser.pugx.org/Tigrov/yii2-pgsql-audit/v/stable)](https://packagist.org/packages/Tigrov/yii2-pgsql-audit)
[![Build Status](https://travis-ci.org/Tigrov/yii2-pgsql-audit.svg?branch=master)](https://travis-ci.org/Tigrov/yii2-pgsql-audit)

Limitation
------------

It is for PostgreSQL only.

The extension optimized for `integer` type of `\Yii::$app->user->id`  
and for `ActiveRecord` models with `integer` type of **primary key**.

If you have different of `integer` types, you can inherit the classes and make the necessary changes. Also you need to make changes in the audit table schema.

* Since 1.2.0 requires PHP >= 5.5
* Since 1.3.0 requires Yii >= 2.0.14.2

Dependents
----------

The extension depends on follow extensions:
* [Tigrov/yii2-pgsql-enum](https://github.com/Tigrov/yii2-pgsql-enum)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist tigrov/yii2-pgsql-audit
```

or add

```
"tigrov/yii2-pgsql-audit": "~1.0"
```

to the require section of your `composer.json` file.

 
Configuration
-------------
Once the extension is installed, configure migrations in `config.php`:

```php
return [
    // ...
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrationController',
        ],
    ],
    // ...
];
```

and apply migrations:

```
yii migrate
```
	
Usage
-----

Add the behavior `AuditableBehavior` to a model class.
```php
class Model extends \yii\db\ActiveRecord
{
    public function behaviors()
    {
        return [
            AuditableBehavior::class,
        ];
    }
}
```

Some examples how you can use it:
```php
$model = new Model;
$model->value = 'a value';
$model->save();

$model->createdAt; // created date and time
$model->createdBy; // instance of \Yii::$app->user->identityClass

// then update it
$model->value = 'new value';
$model->save();

$model->updatedAt; // updated date and time
$model->updatedBy; // instance of \Yii::$app->user->identityClass

// additional features
$model->firstAudit; // \tigrov\pgsql\audit\Audit
$model->lastAudit; // \tigrov\pgsql\audit\Audit

$model->lastAudit->model; // ActiveRecord
$model->lastAudit->user; // instance of \Yii::$app->user->identityClass

$model->lastAudit->revert(); // revert the last changes
$model->firstAudit->revert(); // revert to the first model version

Audit::findByModel($model); // ActiveQuery, to get audit records for the model
Audit::findByUserId($userId); // ActiveQuery, to get audit records for a user
```

License
-------

[MIT](LICENSE)
