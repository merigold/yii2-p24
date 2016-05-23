<?php
/**
 * Created by PhpStorm.
 * User: zakrz
 * Date: 17.05.2016
 * Time: 21:24
 */

namespace merigold\przelewy24\src\models;


use yii\base\Model;

class Przelewy24ConfirmModel extends Model
{

    public $p24_merchant_id;
    public $p24_pos_id;
    public $p24_session_id;
    public $p24_amount;
    public $p24_currency;
    public $p24_order_id;
    public $p24_method;
    public $p24_statment;
    public $p24_sign;

    protected $CRC;

    /**
     * @param mixed $CRC
     */
    public function setCRC($CRC)
    {
        $this->CRC = $CRC;
    }


    public function rules()
    {
        return [
            [['p24_merchant_id','p24_pos_id','p24_session_id','p24_amount','p24_currency','p24_order_id','p24_method','p24_statement','p24_sign'], 'required'],
            [
                [
                    'p24_merchant_id',
                    'p24_pos_id',
                    'p24_amount',
                    'p24_method',
                    'p24_order_id',
                ],
                'integer'
            ],
            [['p24_session_id', 'p24_sign'], 'string', 'max' => 100],
            [['p24_currency'], 'string', 'max' => 3],
            [['p24_statement'], 'string'],
            ['p24_sign','signValidate']

        ];
    }



    public function  signValidate($attribute,$param)
    {
         if($this->signCalculate()!=$this->$attribute)
         {
             $this->addError($attribute, "Niepoprawna suma kontrola");
         }
    }


    private function signCalculate() {


        return md5($this->p24_session_id."|".
            $this->p24_order_id."|".
            $this->p24_amount."|".
            $this->p24_currency."|".
            $this->CRC);

    }


}