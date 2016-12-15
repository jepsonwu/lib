<?php
namespace Jepsonwu\coroutine\core;

/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2016/11/29
 * Time: 9:54
 */
class Task
{
    protected $taskId;
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;

    public function __construct($taskId, \Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $this->stackedCoroutine($coroutine);
    }

    /**
     * coroutine stack
     * @param \Generator $gen
     */
    protected function stackedCoroutine(\Generator $gen)
    {
        $stack = new \SplStack;

        for (; ;) {
            $value = $gen->current();

            if ($value instanceof \Generator) {
                $stack->push($gen);
                $gen = $value;
                continue;
            }

            $isReturnValue = $value instanceof CoroutineReturnValue;
            if (!$gen->valid() || $isReturnValue) {
                if ($stack->isEmpty()) {
                    return;
                }

                $gen = $stack->pop();
                $gen->send($isReturnValue ? $value->getValue() : NULL);
                continue;
            }

            $gen->send(yield $gen->key() => $value);
        }
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function setSendValue($value)
    {
        $this->sendValue = $value;
    }

    public function run()
    {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } else {
            $retval = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            return $retval;
        }
    }

    public function isFinished()
    {
        return !$this->coroutine->valid();
    }
}