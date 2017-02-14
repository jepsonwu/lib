<?php
namespace Jepsonwu\hook;

/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2017/2/14
 * Time: 13:59
 */
class MyCollection implements Collection
{
    public static $hook = [
        "person" => [
            "sex" => 1001,
            "info" => 1002,
        ]
    ];

    public static function getHooks()
    {
        return self::$hook['person'];
    }
}