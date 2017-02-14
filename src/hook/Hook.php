<?php
namespace Jepsonwu\hook;
/**
 * Hook，基于系统调用实现
 *
 * 实例化一个钩子：
 * $hook = new Hook(function ($params) {
 * });
 *
 * 设置钩子参数，调用钩子函数的返回值将会作为最后一个参数传入：
 * $hook['key'] = $value;
 *
 * 暂不支持引用
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

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
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