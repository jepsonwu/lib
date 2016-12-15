<?php
namespace Jepsonwu\coroutine\core;
/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2016/12/1
 * Time: 14:41
 */
class CoroutineReturnValue
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}