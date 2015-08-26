Yii2 Console
===================


Overview
---------

An alternative console `php yii migrate2`


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-source "c006/yii2-console" "dev-master"
```

or add

```
"c006/yii2-console": "dev-master"
```

to the require section of your `composer.json` file.



Required
--------

**console/config/main.php**


>
        'modules'    => [
            ...
            ...
            ...
            /* Use any name you wish */
            'migrate2' => [
                'class' => 'c006\console\Module',
            ],
        ],




Usage
-----

No path or history required, just the migration class name.


>
    php yii migrate2/up m_0000_0000_my_extension
    
>
    php yii migrate2/down m_0000_0000_my_extension

>
    php yii migrate2/update m_0000_0000_my_extension_v2



Notes
--------


+ Use after initial migrate
+ Only works with vendors





Comments / Suggestions
--------------------

Please provide any helpful feedback or requests.

Thanks.



















