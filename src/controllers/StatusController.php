<?php


namespace merigold\przelewy24\src\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;

class StatusController extends \yii\web\Controller
{
    public $layout = false;


    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'verbs' => ['POST'],
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    throw new ForbiddenHttpException('You are not allowed to access this page');
                }
            ]
        ];
    }


    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {

        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }


    public function actionAcceptPayment()
    {

        $p24Connector = Yii::$app->p24;

        $model = $p24Connector->getConfirmationModel();
        $model->load(Yii::$app->request->post(), "");

        if (!$model->validate()) {
            Yii::error("Nie udało się zweryfikować przesłanych danych płatności: " . \yii\helpers\VarDumper::dumpAsString($model->errors),
                \merigold\przelewy24\src\Przelewy24Component::LOG_CATTEGORY);
            Yii::$app->end();
        }

        $event = new \merigold\przelewy24\src\Przelewy24Event();
        $p24Connector->trigger(\merigold\przelewy24\src\Przelewy24Component::EVENT_CONFIRM_ORDER, $event);


        if ($event->isValid) {
            if (!$p24Connector->verifyTransaction()) {
                $p24Connector->trigger(\merigold\przelewy24\src\Przelewy24Component::EVENT_VERIFICATION_FAILED, $event);
            }
        }

        Yii::$app->end();

    }
}