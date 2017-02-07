<?php
namespace Jepsonwu\database\cache;
/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2017/2/7
 * Time: 10:50
 */
class MemcachedCache implements Cache
{
    /**
     * @var \Memcached
     */
    private $cache;

    public function __construct($config)
    {
        $this->cache = new \Memcached();
        $this->cache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 10);
        $this->cache->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
        $this->cache->setOption(\Memcached::OPT_SERVER_FAILURE_LIMIT, 2);
        $this->cache->setOption(\Memcached::OPT_REMOVE_FAILED_SERVERS, true);
        $this->cache->setOption(\Memcached::OPT_RETRY_TIMEOUT, 1);
        $this->cache->addServers($config);
    }

    public function get($key)
    {
        $result = json_decode($this->cache->get($key), true);
        return empty($result) ? [] : $result;
    }

    public function set($key, $value, $expiration = 0)
    {
        return $this->cache->set($key, json_encode($value), $expiration);
    }

    public function delete($key)
    {
        return $this->cache->delete($key);
    }
}