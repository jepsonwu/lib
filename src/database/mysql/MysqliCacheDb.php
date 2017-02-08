<?php
namespace Jepsonwu\database\mysql;

use Jepsonwu\database\cache\Cache;
use Jepsonwu\database\cache\MemcachedCache;
use Jepsonwu\database\exception\MysqlException;

/**
 * support single data cache
 * support multi data cache
 * support transcation
 * support custom component,like "stat cache hit rate"
 * support register custom cache driver,like redis
 *
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2017/2/6
 * Time: 16:29
 */
class MysqliCacheDb extends \MysqliDb
{
    /**
     * table
     * @var
     */
    private $tableName;
    private $primaryKey;//just support single primary key todo multi primary key

    /**
     * @var Cache
     */
    private $cache;
    private $cacheConfig;

    /**
     * CURD
     * @var string
     */
    private $columns = "*";
    private $paginate = null;

    /**
     * transaction
     * @var bool
     */
    private $inTransaction = false;
    private $transactionDeleteCachePrimaryArr = [];


    public function configTable($tableName, $primaryKey)
    {
        $this->tableName = $tableName;
        $this->primaryKey = $primaryKey;
        return $this;
    }

    public function configCache(array $config)
    {
        $this->cacheConfig = !is_array(current($config)) ? [$config] : $config;;
        return $this;
    }

    /**
     * you must be set another cache instance what implements cache interface if you don't use memcached cache
     * @param Cache $cache
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function getTableName()
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
     * @return Cache
     * @throws MysqlException
     */
    private function getCache()
    {
        if (!$this->cache) {
            if (!$this->cacheConfig)
                throw new MysqlException("Mysql db cache config invalid!");

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
        return md5($this->host . $this->port . $this->db . $this->getTableName() . "_" . $primary);
    }

    private function deleteCache(array $primaryArr)
    {
        return $this->getCache()->deleteMulti($primaryArr);
    }

    /**
     * set select columns,array also ok
     * @param string $columns
     * @return $this
     */
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

    public function fetchByPrimaryCache($primary)
    {
        $result = $this->getCache()->get($this->getCacheKey($primary));
        //todo stat cache hit rate

        if (empty($result)) {
            $result = $this->where($this->getPrimaryKey(), $primary)->getOne($this->getTableName());
            $result && $this->getCache()->set($this->getCacheKey($primary), $result, 86400);
        }

        return empty($result) ? [] : $this->filterColumn($result);
    }

    public function fetchOneByCache()
    {
        $result = [];
        $primary = $this->getValue($this->getTableName(), $this->getPrimaryKey());
        $primary && $result = $this->fetchByPrimaryCache($primary);

        return $result;
    }

    /**
     * remain sequence
     * @param array $primaryArr
     * @return array
     */
    public function fetchByPrimaryArrCache(array $primaryArr)
    {
        $result = [];

        foreach ($primaryArr as $primary) {
            $detail = $this->fetchByPrimaryCache($primary);
            $detail && $result[] = $detail;
        }

        return $result;
    }

    public function fetchAllByCache()
    {
        $result = [];
        $primaryArr = $this->getValue($this->getTableName(), $this->getPrimaryKey(), $this->paginate);
        $primaryArr && $result = $this->fetchByPrimaryArrCache($primaryArr);

        return $result;
    }

    /**
     *[
     *  id=>name,
     *  id=>name
     * ]
     * @return array
     */
    public function fetchPairsByCache()
    {
        $result = [];
        $return = $this->fetchAllByCache();
        if ($return) {
            for ($i = 0; $i < count($return); $i++)
                $result[array_shift($return[$i])] = array_shift($return[$i]);
        }

        return $result;
    }

    public function fetchAssocByCache()
    {
        $result = [];
        $return = $this->fetchAllByCache();
        if ($return) {
            for ($i = 0; $i < count($return); $i++)
                $result[current($return[$i])] = $return[$i];
        }

        return $result;
    }

    public function fetchPairsByPrimaryArrCache($primaryArr)
    {
        $result = [];
        $return = $this->fetchByPrimaryArrCache($primaryArr);
        if ($return) {
            for ($i = 0; $i < count($return); $i++)
                $result[array_shift($return[$i])] = array_shift($return[$i]);
        }

        return $result;
    }

    public function fetchAssocByPrimaryArrCache($primaryArr)
    {
        $result = [];
        $return = $this->fetchByPrimaryArrCache($primaryArr);
        if ($return) {
            for ($i = 0; $i < count($return); $i++)
                $result[current($return[$i])] = $return[$i];
        }

        return $result;
    }

    public function paginateByLimit($page, $perPage)
    {
        $page = (int)$page > 1 ? (int)$page : 1;
        $perPage = (int)$perPage > 0 ? (int)$perPage : 0;

        $this->paginate = [($page - 1) * $perPage, $perPage];
        return $this;
    }

    public function paginateByPrimary($lastPrimary, $perPage, $desc = true)
    {
        $lastPrimary = (int)$lastPrimary;
        if ($lastPrimary > 0) {
            $this->where($this->getPrimaryKey(), $lastPrimary, $desc ? "<" : ">");
            $this->orderBy($this->getPrimaryKey(), $desc ? "DESC" : "ASC");
        }
        $this->paginate = (int)$perPage > 0 ? (int)$perPage : 0;
        return $this;
    }

    public function count()
    {
        return (int)$this->getValue($this->getTableName(), "COUNT(1)");
    }

    public function countDistinct()
    {
        return (int)$this->setQueryOption("DISTINCT")->getValue($this->getTableName(), "COUNT(1)");
    }

    public function countDistinctColumn($column)
    {
        return (int)$this->getValue($this->getTableName(), "COUNT(DISTINCT {$column})");
    }

    public function orderByPrimary($desc = true)
    {
        $this->orderBy($this->getPrimaryKey(), $desc ? "DESC" : "ASC");
        return $this;
    }

    public function update($tableName, $tableData, $numRows = null)
    {
        return false;
    }

    public function updateByPrimaryCache($primary, $tableData)
    {
        $this->where($this->getPrimaryKey(), $primary);
        $result = parent::update($this->getTableName(), $tableData);
        if ($result) {
            if ($this->inTransaction)
                $this->transactionDeleteCachePrimaryArr[] = $primary;
            else
                $this->deleteCache([$primary]);
        }

        return $result;
    }

    public function updateByCache($tableData)
    {
        $affectRows = 0;
        $primaryArr = $this->getValue($this->getTableName(), $this->getPrimaryKey(), $this->paginate);
        if ($primaryArr) {
            foreach ($primaryArr as $primary) {
                $ret = $this->updateByPrimaryCache($primary, $tableData);
                $ret && $affectRows += (int)$this->count;
            }
        }

        return $affectRows;
    }

    public function increase($primary, $column, $num = 1)
    {
        $inc = $this->inc($num);
        return $this->updateByPrimaryCache($primary, [$column => $inc]);
    }

    public function decrease($primary, $column, $num = 1)
    {
        $dec = $this->dec($num);
        return $this->updateByPrimaryCache($primary, [$column => $dec]);
    }

    public function delete($tableName, $numRows = null)
    {
        return false;
    }

    public function deleteByPrimaryCache($primary)
    {
        $this->where($this->getPrimaryKey(), $primary);
        $result = parent::delete($this->getTableName(), 1);
        if ($result) {
            if ($this->inTransaction)
                $this->transactionDeleteCachePrimaryArr[] = $primary;
            else
                $this->deleteCache([$primary]);
        }

        return $result;
    }

    public function deleteByCache()
    {
        $affectRows = 0;
        $primaryArr = $this->getValue($this->getTableName(), $this->getPrimaryKey(), $this->paginate);
        if ($primaryArr) {
            foreach ($primaryArr as $primary) {
                $ret = $this->deleteByPrimaryCache($primary);
                $ret && $affectRows++;
            }
        }

        return $affectRows;
    }

    public function rawQuery($query, $bindParams = null)
    {
        return false;
    }

    public function startTransaction()
    {
        $this->inTransaction = true;
        $this->transactionDeleteCachePrimaryArr = [];
        parent::startTransaction();
    }

    public function commit()
    {
        $result = parent::commit();
        $this->deleteCache($this->transactionDeleteCachePrimaryArr);
        return $result;
    }
}