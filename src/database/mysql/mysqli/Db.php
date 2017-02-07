<?php
namespace Jepsonwu\database\mysql\mysqli;

use Jepsonwu\database\cache\Cache;
use Jepsonwu\database\cache\MemcachedCache;
use Jepsonwu\database\exception\MysqlException;

/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2017/2/6
 * Time: 16:29
 */
class Db
{
    /**
     * @var \MysqliDb
     */
    private $db;
    private $connectConfig;

    private $tableName;
    private $primaryKey;//单个主键 todo 多个主键

    /**
     * @var Cache
     */
    private $cache;
    private $enableCache;
    private $cacheConfig;

    private $columns = "*";

    public function __construct($config)
    {
        $this->filterConnectConfig($config);

        try {
            $this->db = new \MysqliDb($config['connect']['host'], $config['connect']['username'], $config['connect']['password'],
                $config['connect']['db'], $config['connect']['port'], $config['connect']['charset']);
            $this->db->mysqli();
        } catch (\Exception $e) {
            throw new MysqlException($e->getMessage());
        }

        $this->initTableConfig($config);
        $this->initCacheConfig($config);
    }

    private function filterConnectConfig(&$config)
    {
        $connect =& $config['connect'];

        if (!isset($connect['host']) || empty($connect['host']))
            throw new MysqlException("Mysql db host invalid!");

        if (!isset($connect['username']) || empty($connect['username']))
            throw new MysqlException("Mysql db username invalid!");

        if (!isset($connect['password']) || empty($connect['password']))
            throw new MysqlException("Mysql db password invalid!");

        if (!isset($connect['db']) || empty($connect['db']))
            throw new MysqlException("Mysql db db invalid!");

        (!isset($connect['port']) || empty($connect['port'])) && $connect['port'] = 3306;
        (!isset($connect['charset']) || empty($connect['charset'])) && $connect['charset'] = "utf8";
        $this->connectConfig = $connect;
    }

    private function initTableConfig(&$config)
    {
        $table =& $config['table'];
        isset($table['name']) && $this->tableName = $table['name'];
        isset($table['primary']) && $this->primaryKey = $table['primary'];
    }

    private function initCacheConfig(&$config)
    {
        $cache =& $config['cache'];
        isset($cache['enable']) && $cache['enable'] && $this->enableCache = true;
        if (isset($cache['connect'])) {
            !is_array(current($cache['connect'])) && $cache['connect'] = [$cache['connect']];
            $this->cacheConfig = $cache['connect'];
        }
    }

    private function getTableName()
    {
        if (!$this->tableName)
            throw new MysqlException("Mysql db tablename invalid!");

        return $this->tableName;
    }

    private function getPrimaryKey()
    {
        if (!$this->primaryKey)
            throw new MysqlException("Mysql db primary key invalid!");

        return $this->primaryKey;
    }

    /**
     * @return \MysqliDb
     */
    private function getDb()
    {
        return $this->db;
    }

    private function enableCache()
    {
        return $this->enableCache ? true : false;
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return Cache
     * @throws MysqlException
     */
    private function getCache()
    {
        if (!$this->cache) {
            try {
                $this->cache = new MemcachedCache($this->cacheConfig);
            } catch (\Exception $e) {
                throw new MysqlException("Mysql db cache invalid,error:" . $e->getMessage());
            }
        }

        return $this->cache;
    }

    private function getCacheKey($primary)
    {
        return md5($this->connectConfig['host'] . $this->connectConfig['port'] . $this->connectConfig['db'] .
            $this->getTableName() . "_" . $primary);
    }

    public function column($columns = "*")
    {
        $this->columns = $columns;
        return $this;
    }

    private function filterColumn($result)
    {
        if ($this->columns == "*")
            return $result;

        $columns = explode(",", $this->columns);
        $columns = array_combine($columns, array_fill(0, count($columns), 1));

        $result && $result = array_intersect_key($result, $columns);

        return $result;
    }

    public function fetchByPrimary($primary)
    {
        $result = [];
        if ($this->enableCache()) {
            $result = $this->getCache()->get($this->getCacheKey($primary));
            $result = $this->filterColumn($result);
            //todo 缓存命中率
        }

        if (empty($result)) {
            $result = $this->getDb()->where($this->getPrimaryKey(), $primary)->getOne($this->getTableName(), $this->columns);
            $result && $this->enableCache() && $this->getCache()->set($this->getCacheKey($primary), $result);
        }

        return $result;
    }

    public function fetchOne($columns = "*")
    {
        if ($this->enableCache()) {

        } else {
            $result = $this->getDb()->getOne($this->getTableName(), $columns);
        }

        return $result;
    }

    public function fetchAll()
    {
        $result = $this->dbCache->selectAll($this->queryParams, $this->queryBind);
        return $this->formatResult ? $this->formatData($result) : $result;
    }

    /**
     * 生成键值对数据
     * [['id'=>'1','name'=>'aa'],['id'=>2,'name'=>'cc']] ===>>
     * ['1'=>'aa','2'=>'cc']
     * @return array
     */
    public function fetchPairs()
    {
        $return = [];

        $result = $this->fetchAll();
        if ($result) {
            for ($i = 0; $i < count($result); $i++)
                $return[array_shift($result[$i])] = array_shift($result[$i]);
        }

        return $return;
    }

    /**
     * 第一个字段作为键 其余作为值
     * @return array
     */
    public function fetchAssoc()
    {
        $return = [];

        $result = $this->fetchAll();
        if ($result) {
            for ($i = 0; $i < count($result); $i++)
                $return[current($result[$i])] = $result[$i];
        }

        return $return;
    }

    /**
     * 通过ID获取 使用in条件时建议调用 1.使用cache 2.保证顺序
     * @param array $idArr
     * @return array|mixed
     */
    public function fetchAllByIds(array $idArr)
    {
        $result = [];
        $fields = $this->queryParams['field'] == "*" ? [] : explode(",", $this->queryParams['field']);
        $fields && $fields = array_combine($fields, array_fill(0, count($fields), 1));

        foreach ($idArr as $id) {
            $detail = $this->selectOne($id);
            if ($detail) {
                $result[] = $fields ? array_intersect_key($detail, $fields) : $detail;
            }
        }

        return $this->formatResult ? $this->formatData($result) : $result;
    }

    public function fetchPairsByIds(array $idArr)
    {
        $return = [];

        $result = $this->fetchAllByIds($idArr);
        if ($result) {
            for ($i = 0; $i < count($result); $i++)
                $return[array_shift($result[$i])] = array_shift($result[$i]);
        }

        return $return;
    }

    public function fetchAssocByIds(array $idArr)
    {
        $return = [];

        $result = $this->fetchAllByIds($idArr);
        if ($result) {
            for ($i = 0; $i < count($result); $i++)
                $return[current($result[$i])] = $result[$i];
        }

        return $return;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }
}