<?php
namespace Jepsonwu\coroutine\core;
/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2016/12/1
 * Time: 14:45
 */
class CoSocket
{
    protected $socket;

    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    public function accept()
    {
        yield $this->waitForRead($this->socket);
        yield $this->retval(new CoSocket(stream_socket_accept($this->socket, 0)));
    }

    public function read($size)
    {
        yield $this->waitForRead($this->socket);
        yield $this->retval(fread($this->socket, $size));
    }

    public function write($string)
    {
        yield $this->waitForWrite($this->socket);
        fwrite($this->socket, $string);
    }

    public function close()
    {
        @fclose($this->socket);
    }

    public function waitForRead($socket)
    {
        return new SystemCall(
            function (Task $task, Scheduler $scheduler) use ($socket) {
                $scheduler->waitForRead($socket, $task);
            }
        );
    }

    public function waitForWrite($socket)
    {
        return new SystemCall(
            function (Task $task, Scheduler $scheduler) use ($socket) {
                $scheduler->waitForWrite($socket, $task);
            }
        );
    }

    function retval($value)
    {
        return new CoroutineReturnValue($value);
    }
}