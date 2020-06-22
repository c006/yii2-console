Yii2 Console
===================


Overview
---------

An alternative console to `php yii migrate`


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-source "c006/yii2-console" "*"
```

or add

```
"c006/yii2-console": "*"
```

to the require section of your `composer.json` file.



Required
--------

**console/config/main.php**


>
        'modules'    => [
            'migrate2' => [
                'class' => 'c006\console\Module',
            ],
        ],




Usage
-----

Easy to use!

No path or history required, just the migration class name.
Default action is "up"

>
    php yii migrate2 m_0000_0000_my_extension
    
>
    php yii migrate2/down m_0000_0000_my_extension

>
    php yii migrate2/custom m_0000_0000_my_extension



Notes
--------

+ Use after initial migrate
+ Only works with vendors


Comments / Suggestions
--------------------

Please provide any helpful feedback or requests.

Thanks.



















