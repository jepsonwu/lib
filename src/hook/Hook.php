<?php
namespace Jepsonwu\hook;
/**
 * hook
 * $hook = new Hook(function ($user_detail, $peel_detail, $comment_detail) {
 * });
 * $hook['user_detail'] = $user_detail;
 * $hook['peel_detail'] = $peel_detail;
 *
 *
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2016/12/29
 * Time: 9:50
 */
class Hook implements \ArrayAccess
{
    protected $callback;
    protected $params = [];
    private static $hooks = [];

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public static function register($name, callable $function)
    {
        self::$hooks[$name] = $function;
    }

    public static function get($name)
    {
        return isset(self::$hooks[$name]) && is_callable(self::$hooks[$name]) ? self::$hooks[$name] : false;
    }

    public static function run($name, $result)
    {
        if ($result !== false) {
            $callback = self::get($name);
            $callback && $result = call_user_func_array($callback, [$result]);
        }

        return $result;
    }

    public static function exec($result = null)
    {
        if ($result !== false) {
            $class = $function = "";
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $trace[1]['class'] && $class = $trace[1]['class'];
            $class = substr($class, strrpos($class, "\\") + 1, -7);
            $trace[1]['function'] && $function = $trace[1]['function'];

            $ret = preg_match_all("/[a-z|A-Z]{1}[a-z,0-9]+/", $function, $function);
            $ret && $function[0] &&
            $function = implode("_", array_map("strtolower", $function[0]));

            $ret = preg_match_all("/[A-Z]{1}[a-z,0-9]+/", $class, $class);
            $ret && $class[0] &&
            $class = implode("_", array_map("strtolower", $class[0]));

            $hooks = &\ServiceHookMacro::${$class}[$function];
            if (isset($hooks) && is_array($hooks)) {
                asort($hooks);
                foreach ($hooks as $hook => $name) {
                    $result = self::run($name, $result);
                }
            }
        }
        return $result;
    }

    /**
     * 暂不支持引用
     * @return mixed
     */
    public function __invoke()
    {
        $args = func_get_args();
        $callback = $this->callback;
        return call_user_func_array($callback, $this->params + $args);
    }

    public function offsetGet($offset)
    {
        return isset($this->params[$offset]) ? $this->params[$offset] : false;
    }

    public function offsetSet($offset, $value)
    {
        $this->params[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->params[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->params[$offset]);
    }
}