<?php
namespace Jepsonwu\coroutine\pcntl;

use Jepsonwu\coroutine\core\SystemCall;
use Jepsonwu\coroutine\core\Task;
use Jepsonwu\coroutine\core\Scheduler;

/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2016/11/29
 * Time: 12:00
 */
class Pcntl
{
    public function __construct()
    {
    }

    public function getTaskId()
    {
        return new SystemCall(function (Task $task, Scheduler $scheduler) {
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        });
    }

    public function newTask(\Generator $coroutine)
    {
        return new SystemCall(function (Task $task, Scheduler $scheduler) use ($coroutine) {
            $task->setSendValue($scheduler->newTask($coroutine));
            $scheduler->schedule($task);
        });
    }

    public function killTask($tid)
    {
        return new SystemCall(function (Task $task, Scheduler $scheduler) use ($tid) {
            $task->setSendValue($scheduler->killTask($tid));
            $scheduler->schedule($task);
        });
    }
}