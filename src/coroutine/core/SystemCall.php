<?php
namespace Jepsonwu\coroutine\core;
/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2016/11/29
 * Time: 10:59
 */
class SystemCall
{
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Task $task, Scheduler $scheduler)
    {
        $callback = $this->callback;
        return $callback($task, $scheduler);
    }
}