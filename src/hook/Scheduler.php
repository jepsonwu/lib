<?php
namespace Jepsonwu\Hook;
/**
 * 通用场景是：函数返回值作为参数传入钩子调度器，如果为false则不执行钩子，亦可以不传参数
 *
 * Schedule::run($name,$result)
 *
 * 支持同一方法多个钩子连续执行，按ASCII码排序，你需要实现Collection接口getHooks方法
 * Schedule::exec($result)
 *
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2017/2/14
 * Time: 10:03
 */
class Scheduler
{
    private static $hooks = [];

    /**
     * @var Collection
     */
    private static $collection;

    public static function register($name, callable $function)
    {
        self::$hooks[$name] = $function;
    }

    public static function get($name)
    {
        return isset(self::$hooks[$name]) && is_callable(self::$hooks[$name]) ? self::$hooks[$name] : false;
    }

    public static function run($name, $result = null)
    {
        if ($result !== false) {
            $callback = self::get($name);
            $callback && $result = call_user_func_array($callback, is_null($result) ? [] : [$result]);
        }

        return $result;
    }

    /**
     * @param Collection $collection
     */
    public static function registerCollection(Collection $collection)
    {
        self::$collection = $collection;
    }

    private static function getHooks()
    {
        $collection = self::$collection;
        if (is_null($collection))
            throw new \Exception("must be register collection");

        $hooks = (array)$collection::getHooks();
        asort($hooks);
        return $hooks;
    }

    public static function exec($result = null)
    {
        if ($result !== false) {
            foreach (self::getHooks() as $hook => $name) {
                $result = self::run($name, $result);
            }
        }
        return $result;
    }

}