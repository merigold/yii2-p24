<?php
/**
 * Created by PhpStorm.
 * User: zakrz
 * Date: 23.05.2016
 * Time: 11:14
 */

namespace merigold\przelewy24\src;


use yii\base\Event;

interface Przelewy24EventHandler
{

    public function handleOrderConfirmation(Przelewy24Event $event);


    public function handleOrderVerificationFailed(Przelewy24Event $event);
    
}