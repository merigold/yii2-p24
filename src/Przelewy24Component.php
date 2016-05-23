<?php
/**
 * Przelewy24 Yii component
 *
 * api docs:
 * https://www.przelewy24.pl/storage/app/media/pobierz/Instalacja/przelewy24_specyfikacja_3_2.pdf
 */

namespace merigold\przelewy24\src;


use HTTP_Request2;
use HTTP_Request2_Exception;
use merigold\przelewy24\src\models\Przelewy24ConfirmModel;
use merigold\przelewy24\src\models\Przelewy24Model;
use Yii;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\VarDumper;

final class Przelewy24Component extends Component
{

    const LOG_CATTEGORY = 'Przelewy24';
    const API_VERSION = '3.2';
    const PROD_URL = 'https://secure.przelewy24.pl';
    const TEST_URL = 'https://sandbox.przelewy24.pl';

    const ACTION_TEST_CONNECTION = 'testConnection';
    const ACTION_SEND_TRANSACTION = 'trnDirect';
    const ACTION_VERIFY_TRANSACTION = 'trnVerify';

    const EVENT_CONFIRM_ORDER = 'p24_event_confirm_order';
    const EVENT_VERIFICATION_FAILED = 'p24_event_veryfication_failed';
    
    public $testMode = true;
    public $CRC;
    public $merchant_id;
    public $pos_id;
    
    public $eventHandlerClassName;

    protected $signHash;

    /**
     * @var Przelewy24Model
     */
    protected $model;


    /**
     * @var Przelewy24ConfirmModel
     */
    protected $confirmationModel;

    /**
     * @var Przelewy24EventHandler
     */
    protected $eventHandler;

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub

        if ($this->CRC == null) {
            throw  new InvalidConfigException('CRC is required');
        }
        if ($this->merchant_id == null) {
            throw  new InvalidConfigException('merchant_id is required');
        }
        if ($this->pos_id == null) {
            throw  new InvalidConfigException('pos_id is required');
        }
        
        
        if($this->eventHandler!=null)
        {
            $this->eventHandler = new $this->eventHandlerClassName;

            if(!$this->eventHandler instanceof Przelewy24EventHandler)
            {
                throw  new InvalidConfigException('eventHandlerClassName must implement Przelewy24EventHandler interface');
            }

            $this->on(self::EVENT_CONFIRM_ORDER,[$this->eventHandler,'handleOrderConfirmation']);
            $this->on(self::EVENT_VERIFICATION_FAILED,[$this->eventHandler,'handleOrderVerificationFailed']);

        }




    }


    public function testConnection()
    {
        $params = [
            'p24_merchant_id' => $this->merchant_id,
            'p24_pos_id' => $this->pos_id,
            'p24_sign' => $this->generateSign(),
        ];

        $result = $this->callUrl(self::ACTION_TEST_CONNECTION, $params);

        if(isset($result['error']) && $result['error']==0)
            return true;
        else
        {
            Yii::error(VarDumper::dumpAsString($result),self::LOG_CATTEGORY);
            return false;
        }

    }



    public function getModel()
    {
        if($this->model==null)
        {
            $this->model = new Przelewy24Model();
            $this->model->p24_merchant_id = $this->merchant_id;
            $this->model->p24_pos_id = $this->merchant_id;
            $this->model->p24_session_id = Yii::$app->session->id;
            $this->model->p24_api_version = self::API_VERSION;
            $this->model->setCRC($this->CRC);
        }

        return $this->model;

    }


    public function getConfirmationModel()
    {
        if($this->confirmationModel==null)
        {
            $this->confirmationModel = new Przelewy24ConfirmModel();
            $this->confirmationModel->setCRC($this->CRC);
        }

        return $this->confirmationModel;
    }



    public function renderFormFields()
    {
        $html = "";
        foreach ($this->model->getAttributes() as $name=>$value)
        {
            if(strrpos($name,'_X'))
            {
                $i=1;
               foreach($value as $item)
               {
                   $html.=Html::HiddenInput(str_replace("X",$i,$name),$value);
               }
            }
            else
            {
                $html.=Html::HiddenInput($name,$value);
            }
        }


         return $html;
    }

    public function getFormActionUrl()
    {
        $url = $this->testMode?self::TEST_URL:self::PROD_URL;
        $url.="/".self::ACTION_SEND_TRANSACTION;
        return $url;
    }


    public function verifyTransaction()
    {
        $params = [
            'p24_merchant_id' =>$this->confirmationModel->p24_merchant_id,
            'p24_pos_id' =>$this->confirmationModel->p24_pos_id,
            'p24_session_id'=>$this->confirmationModel->p24_session_id,
            'p24_amount'=>$this->confirmationModel->p24_amount,
            'p24_currency'=>$this->confirmationModel->p24_currency,
            'p24_order_id'=>$this->confirmationModel->p24_order_id,
            'p24_sign' => $this->confirmationModel->p24_sign,
        ];

        $result = $this->callUrl(self::ACTION_V, $params);

        if(isset($result['error']) && $result['error']==0)
            return true;
        else
        {
            Yii::error(VarDumper::dumpAsString($result),self::LOG_CATTEGORY);
            return false;
        }
    }

    private function generateSign()
    {
        return md5($this->pos_id . "|" . $this->CRC);
    }

    private function callUrl($action, $params)
    {

        $url = $this->testMode ? self::TEST_URL : self::PROD_URL;
        $url .= "/" . $action;
        
        $request = $this->initCurlResource($url, $params);
        try {
            $response = $request->send();
            if (200 == $response->getStatus()) {
                return $this->parseCurlResponse($response->getBody());
            } else {
                $msg = 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .$response->getReasonPhrase();
                throw new HTTP_Request2_Exception($msg);
            }
        } catch (HTTP_Request2_Exception $e) {
            Yii::error('Curl exec error: ' . VarDumper::dumpAsString($e->getMessage()),self::LOG_CATTEGORY);
            throw new InvalidCallException('Curl exec error '.$e->getMessage(), 203);
        }

    }

    /**
     * @param $url
     * @param $params
     * @return HTTP_Request2
     * @throws \HTTP_Request2_LogicException
     */
    private function initCurlResource($url, $params)
    {

        $request = Yii::$container->get('HTTP_Request2',[
            $url, HTTP_Request2::METHOD_POST,[
                'ssl_verify_peer'=>false,
                'ssl_verify_host'=>false,
                ''
            ]
        ]);

        //$request = new HTTP_Request2();
        $request->addPostParameter($params);
        $user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
        $request->setHeader(['User-Agent'=>$user_agent ]);
        return $request;
    }

    private function parseCurlResponse($response)
    {

        $responseArray = [];
        $rows = explode("&", $response);

        foreach ($rows as $row) {
            list($key, $value) = explode("=", $row);
            $responseArray[$key] = $value;
        }

        return $responseArray;

    }


}