<?php
/**
 * Created by PhpStorm.
 * User: zakrz
 * Date: 23.05.2016
 * Time: 11:33
 */

namespace merigold\przelewy24\src;


use yii\base\Event;

class Przelewy24Event extends Event
{


    /**
     * @var bool
     */
    public $isValid = true;

}