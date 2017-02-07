<?php
namespace Jepsonwu\database\cache;
/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2017/2/7
 * Time: 10:45
 */
interface Cache
{
    public function get($key);

    public function set($key, $value, $expiration);

    public function delete($key);
}