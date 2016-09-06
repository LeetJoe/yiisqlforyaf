<?php

/**
 * 通用数据访问方法封装
 * @author gorden
 * @time 15/5/5
 */
class Db_Dao {

    private $_tableName = '';
    private static $_dbName = 'db';
    private $_text = '';

    /**
     * @var Db_Connection
     */
    private static $_connection = null;

    public function __construct($tableName, $dbName = 'db') {
        $this->setTableName($tableName);
        $this->setDbName($dbName);
    }

    /**
     * 调用该方法，设置当前Model访问的数据库
     *
     * @param $tableName string
     */
    public function setDbName($dbName) {
        $this->_sbName = $dbName;
    }

    /**
     * 调用该方法，设置当前Model访问的数据表
     *
     * @param $tableName string
     */
    public function setTableName($tableName) {
        $this->_tableName = $tableName;
    }

    /**
     * 获得当前连接对象
     *
     * @return Db_Connection
     */
    public static function getConnection() {
        if (empty(self::$_connection)) {
            self::$_connection = Db_Connection::instance(self::$_dbName);
        }
        return self::$_connection;
    }

    /**
     * 设置当前连接
     *
     * @param $connection Db_Connection
     */
    public function setConnection($connection) {
        $this->_connection = $connection;
    }

    /**
     * 通用查询函数
     *
     * @param      $columns array|string 查询字段
     * @param      $conditions array 查询条件
     * @param      $limit bool|int 分页大小
     * @param      $offset int 分页偏移量
     * @param      $order bool|string 排序
     * @param      $group bool|string 分组字段
     *
     * @return array|Db_DataReader
     * @throws Exception
     */
    public function query($columns, $conditions, $limit = false, $offset = 0, $order = false, $group = false, $having = false, $distinct = false) {
        $cmd    = $this->getConnection()->createCommand();
        $select = $cmd->from($this->_tableName)->select($columns);
        foreach ($conditions as $key => $value) {
            if (is_int($key)) {
                //OR, IN等情况，值是三元组
                if (count($value) != 3) {
                    throw new Exception('无效的查询参数:' . json_encode(array($key => $value), JSON_UNESCAPED_UNICODE));
                }
                $select->andWhere($value);
            } elseif (strpos($key, ' ') === false) {
                //简写场景，key是条件列，value是值
                $select->andWhere("`$key` = :$key", array($key => $value));
            } elseif (is_array($value)) {
                //key是条件，value是值
                $select->andWhere($key, $value);
            } else {
                //未知场景
                throw new Exception('不可识别的查询条件:' . json_encode(array($key => $value), JSON_UNESCAPED_UNICODE));
            }
        }
        if (!empty($group)) {
            $select->group($group);
            if (!empty($having)) {
                $select->having($having);
            }
        }
        if (!empty($order)) {
            $select->order($order);
        }
        if (!empty($limit)) {
            $select->limit($limit);
        }

        $select->setDistinct($distinct);

        $select->offset($offset);

        $startTime = microtime(true);
        $this->_text = $select->getText();
        $result = $select->queryAll();
        # Log::info('DB_QUERY', array($this->_tableName, $columns, $conditions, $limit, $offset, $order, $group, $distinct, (is_array($result)?count($result):0), microtime(true) - $startTime));
        return $result;
    }

    public function getText() {
        return $this->_text;
    }

    public function insert($columns) {
        $cmd = $this->getConnection()->createCommand();
        $startTime = microtime(true);
        $result = $cmd->insert($this->_tableName, $columns);
        # Log::info('DB_INSERT', array($this->_tableName, $columns, $result, microtime(true) - $startTime));

        return $result;
    }

    /**
     * 根据主键或唯一索引，先删除已有记录，然后再插入新记录
     *
     * @param $columns
     *
     * @return int
     */
    public function replace($columns) {
        $conn         = $this->getConnection();
        $params       = array();
        $names        = array();
        $placeholders = array();
        foreach ($columns as $name => $value) {
            $names[] = $conn->quoteColumnName($name);
            if ($value instanceof Db_Schema_Expression) {
                $placeholders[] = $value->expression;
                foreach ($value->params as $n => $v)
                    $params[$n] = $v;
            } else {
                $placeholders[]      = ':' . $name;
                $params[':' . $name] = $value;
            }
        }
        $sql = 'REPLACE INTO ' . $conn->quoteTableName($this->_tableName)
            . ' (' . implode(', ', $names) . ') VALUES ('
            . implode(', ', $placeholders) . ')';
        $startTime = microtime(true);
        $result = $conn->createCommand()->setText($sql)->execute($params);
        # Log::info('DB_REPLACE', array($this->_tableName, $columns, $result, microtime(true) - $startTime));
        return $result;
    }

    public function update($columns, $condition) {
        $cmd = $this->getConnection()->createCommand();

        $where  = $condition;
        $params = array();
        if (is_array($condition)) {
            $where = array('AND');
            foreach ($condition as $key => $value) {
                if (is_int($key)) {
                    //OR, IN等情况，值是三元组
                    if (count($value) != 3) {
                        throw new Exception('无效的查询参数:' . json_encode(array($key => $value), JSON_UNESCAPED_UNICODE));
                    }
                    $where[] = $value;
                } elseif (strpos($key, ' ') === false) {
                    //简写场景，key是条件列，value是值
                    $where[]              = "`$key` = :where_$key";
                    $params["where_$key"] = $value;
                } elseif (is_array($value)) {
                    //key是条件，value是值
                    $where[] = $key;
                    foreach ($value as $k => $v) {
                        $params[$k] = $v;
                    }
                } else {
                    //未知场景
                    throw new Exception('不可识别的查询条件:' . json_encode(array($key => $value), JSON_UNESCAPED_UNICODE));
                }
            }
        }
        $startTime = microtime(true);
        $result = $cmd->update($this->_tableName, $columns, $where, $params);
        # Log::info('DB_UPDATE', array($this->_tableName, $columns, $condition, $result, microtime(true) - $startTime));
        return $result;
    }

    public function delete($condition) {
        $cmd    = $this->getConnection()->createCommand();
        $where  = $condition;
        $params = array();
        if (is_array($condition)) {
            $where = array('AND');
            foreach ($condition as $key => $value) {
                if (is_int($key)) {
                    //OR, IN等情况，值是三元组
                    if (count($value) != 3) {
                        throw new Exception('无效的查询参数:' . json_encode(array($key => $value), JSON_UNESCAPED_UNICODE));
                    }
                    $where[] = $value;
                } elseif (strpos($key, ' ') === false) {
                    //简写场景，key是条件列，value是值
                    $where[]              = "`$key` = :where_$key";
                    $params["where_$key"] = $value;
                } elseif (is_array($value)) {
                    //key是条件，value是值
                    $where[] = $key;
                    foreach ($value as $k => $v) {
                        $params[$k] = $v;
                    }
                } else {
                    //未知场景
                    throw new Exception('不可识别的查询条件:' . json_encode(array($key => $value), JSON_UNESCAPED_UNICODE));
                }
            }
        }
        $startTime = microtime(true);
        $result = $cmd->delete($this->_tableName, $where, $params);
        # Log::info('DB_DELETE', array($this->_tableName, $condition, $result, microtime(true) - $startTime));
        return $result;
    }

    /**
     * Starts a transaction.
     * @return Db_Transaction the transaction initiated
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    public function getTableName() {
        return $this->_tableName;
    }
}