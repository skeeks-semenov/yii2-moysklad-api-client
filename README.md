Yii2 moysklad api client
===================================

https://dev.moysklad.ru/doc/api/


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist skeeks/yii2-moysklad-api-client "*"
```

or add

```
"skeeks/yii2-moysklad-api-client": "*"
```


Configure your application
----------

```php
//App config
[
    'components'    =>
    [
    //....
        'moyskladApiClient' =>
        [
            'class'         => 'skeeks\yii2\moyskladApiClient\MoyskladApiClient',
            'email'         => '',
            'password'      => '',
        ],
    //....
    ]
]

```
How to use
----------

```php
\Yii::$app->moyskladApiClient->getProfileApiMethod();
```

___

> [![skeeks!](https://skeeks.com/img/logo/logo-no-title-80px.png)](https://skeeks.com)  
<i>SkeekS CMS (Yii2) — fast, simple, effective!</i>  
[skeeks.com](https://skeeks.com) | [cms.skeeks.com](https://cms.skeeks.com)

