<?php

/**
 * Created by PhpStorm.
 * User: zakrz
 * Date: 20.05.2016
 * Time: 21:29
 */
class StatusController extends \yii\web\Controller
{
    public $layout=false;



    public function actionAcceptPayment()
    {

        $p24Connector = Yii::$app->p24;

        $model = $p24Connector->getConfirmationModel();
        $model->load(Yii::$app->request->post(),"");

        if(!$model->validate())
        {
            Yii::error("Nie udało się zweryfikować przesłanych danych płatności: " . \yii\helpers\VarDumper::dumpAsString($model->errors),\merigold\przelewy24\src\Przelewy24Component::LOG_CATTEGORY);
            Yii::$app->end();
        }

        $event = new \merigold\przelewy24\src\Przelewy24Event();
        $p24Connector->trigger(\merigold\przelewy24\src\Przelewy24Component::EVENT_CONFIRM_ORDER,$event);


        if($event->isValid)
        {
            if(!$p24Connector->verifyTransaction())
                $p24Connector->trigger(\merigold\przelewy24\src\Przelewy24Component::EVENT_VERIFICATION_FAILED,$event);
        }

        Yii::$app->end();
        
    }
}