# Yii2 - przelewy24

Yii2 component of online payment [przelewy24](http://http://przelewy24.pl/)

Install
-

```

require: {
   "merigold/yii2-p24": "~1.0"
}

```

or

```

composer require "merigold/yii2-p24:~1.0"

```


Usage
-

**config file:**


```PHP
'components' => [
       ...
        'p24'=>[
            'class'=>\merigold\przelewy24\src\Przelewy24Component::className(),
            'merchant_id'=><MERCHANT ID>,
            'pos_id'=><SHOP ID (default merchant id)>,
            'testMode'=>true // true to connect to sandbox panel,
            'eventHandlerClassName'=><ClassName of order accept event Handler>,
            'CRC'=><CRC> //secret CRC from przelewy24 panel,
        ]
],
'modules' => [
	...
     'przelewy24' => [
            'class' => '\merigold\przelewy24\src\Przelewy24Module',
        ],
]

```

**create from controller:**

```php

public function actionIndex()
    {

        $p24Connector = Yii::$app->p24;
        $model = $p24Connector->Model;

        $model->p24_amount = <your params>;
        $model->p24_currency  = <your params>;
        $model->p24_description  = <your params>;
        $model->p24_email  = <your params>;
        $model->p24_country  = <your params>;
        $model->p24_url_return  = <your params>;
        $model->p24_session_id = <your unique session id e.g. order_id+user_id+session_id

		... other payment params

        return $this->render('index',['p24Connector'=>$p24Connector]);

    }
```


**view file:**

```php
<?= Html::beginForm($p24Connector->FormActionUrl) ?>

<?=$p24Connector->renderFormFields()?>

<?= Html::submitButton('submit') ?>

<?= Html::endForm() ?>
```


**Create EventHandler Class**

Success callback from przelewy24 call action: przelewy24/status/accept-payment

This acction after checksum validate triiger handleOrderConfirmation().


```php

class AcceptOrderEvent implements \merigold\przelewy24\src\Przelewy24EventHandler
{

    public function handleOrderConfirmation(\merigold\przelewy24\src\Przelewy24Event $event)
    {

        try {

			//save order to db
            //callback POST params in: $event->sender->ConfirmationModel

            //trnVerify call only when
            $event->isValid = true;

        } catch (\Exception $e) {
            \Yii::error(VarDumper::dumpAsString($e->getMessage()));
            $event->isValid = false;
        }


    }

	/**
    * event for trnVerify failed
    */
    public function handleOrderVerificationFailed(\merigold\przelewy24\src\Przelewy24Event $event)
    {

        \Yii::trace("Order VerificationFailed");

    }
}

```






