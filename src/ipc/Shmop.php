<?php
namespace Jepsonwu\ipc;
/**
 * system v shared memory
 * Created by PhpStorm.
 * User: shanzha
 * Date: 2016/12/20
 * Time: 19:26
 */

class Shmop
{
    static private $instance = null;

    static public function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
    }

    /**
     * @param $key
     * @return bool|string
     */
    public function read($key)
    {
        !is_int($key) && $key = $this->stringToInt($key);
        @$shm_id = shmop_open($key, "a", 0, 0);
        if ($shm_id) {
            $return = shmop_read($shm_id, 0, shmop_size($shm_id));
            if ($return)
                return unserialize($return);
        }
        return false;
    }

    /**
     * @param string $key
     * @param $value mixed
     * @return bool|int
     */
    public function write($key, $value)
    {
        !is_int($key) && $key = $this->stringToInt($key);
        $value = serialize($value);
        @$shm_id = shmop_open($key, "c", 0644, strlen($value));
        if ($shm_id)
            return shmop_write($shm_id, $value, 0);
        return false;
    }

    /**
     * @param $key
     * @return bool
     */
    public function delete($key)
    {
        !is_int($key) && $key = $this->stringToInt($key);
        @$shm_id = shmop_open($key, "a", 0, 0);
        $return = false;
        $shm_id && $return = shmop_delete($shm_id);
        $return && shmop_close($shm_id);
        return $return;
    }

    /**
     * 字符串转int
     * @param $string
     * @return int
     */
    protected function stringToInt($string)
    {
        $int = 0;
        for ($i = 0; $i < strlen($string); $i++)
            $int += ord($string{$i});
        return $int + 4000000000;
    }
}