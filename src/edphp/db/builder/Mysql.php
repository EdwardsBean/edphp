<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://edphpphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace edphp\db\builder;

use edphp\db\Builder;
use edphp\db\Expression;
use edphp\db\Query;

/**
 * mysql数据库驱动
 */
class Mysql extends Builder
{
    // 查询表达式解析
    protected $parser = [
        'parseCompare'     => ['=', '<>', '>', '>=', '<', '<='],
        'parseLike'        => ['LIKE', 'NOT LIKE'],
        'parseBetween'     => ['NOT BETWEEN', 'BETWEEN'],
        'parseIn'          => ['NOT IN', 'IN'],
        'parseExp'         => ['EXP'],
        'parseRegexp'      => ['REGEXP', 'NOT REGEXP'],
        'parseNull'        => ['NOT NULL', 'NULL'],
        'parseBetweenTime' => ['BETWEEN TIME', 'NOT BETWEEN TIME'],
        'parseTime'        => ['< TIME', '> TIME', '<= TIME', '>= TIME'],
        'parseExists'      => ['NOT EXISTS', 'EXISTS'],
        'parseColumn'      => ['COLUMN'],
    ];

    protected $insertAllSql = '%INSERT% INTO %TABLE% (%FIELD%) VALUES %DATA% %COMMENT%';
    protected $updateSql    = 'UPDATE %TABLE% %JOIN% SET %SET% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * 生成insertall SQL
     * @access public
     * @param  Query     $query   查询对象
     * @param  array     $dataSet 数据集
     * @param  bool      $replace 是否replace
     * @return string
     */
    public function insertAll(Query $query, $dataSet, $replace = false, $dupliateUpdates = [])
    {
        $options = $query->getOptions();

        // 获取合法的字段
        if ('*' == $options['field']) {
            $allowFields = $this->connection->getTableFields($options['table']);
        } else {
            $allowFields = $options['field'];
        }

        // 获取绑定信息
        $bind = $this->connection->getFieldsBind($options['table']);

        foreach ($dataSet as $k => $data) {
            $data = $this->parseData($query, $data, $allowFields, $bind, '_' . $k);

            $values[] = '( ' . implode(',', array_values($data)) . ' )';

            if (!isset($insertFields)) {
                $insertFields = array_keys($data);
            }
        }

        $fields = [];
        foreach ($insertFields as $field) {
            $fields[] = $this->parseKey($query, $field);
        }

        if (!empty($dupliateUpdates)) {
            return str_replace(
                ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
                [
                    'INSERT',
                    $this->parseTable($query, $options['table']),
                    implode(' , ', $fields),
                    implode(' , ', $values) . $this->parseDuplicate($query, $dupliateUpdates),
                    $this->parseComment($query, $options['comment']),
                ],
                $this->insertAllSql);
        } else {
            return str_replace(
                ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
                [
                    $replace ? 'REPLACE' : 'INSERT',
                    $this->parseTable($query, $options['table']),
                    implode(' , ', $fields),
                    implode(' , ', $values),
                    $this->parseComment($query, $options['comment']),
                ],
                $this->insertAllSql);
        }
    }

    /**
     * 正则查询
     * @access protected
     * @param  Query        $query        查询对象
     * @param  string       $key
     * @param  string       $exp
     * @param  Expression   $value
     * @param  string       $field
     * @return string
     */
    protected function parseRegexp(Query $query, $key, $exp, Expression $value, $field)
    {
        return $key . ' ' . $exp . ' ' . $value->getValue();
    }

    /**
     * 字段和表名处理
     * @access public
     * @param  Query     $query 查询对象
     * @param  mixed     $key   字段名
     * @param  bool      $strict   严格检测
     * @return string
     */
    public function parseKey(Query $query, $key, $strict = false)
    {
        if (is_numeric($key)) {
            return $key;
        } elseif ($key instanceof Expression) {
            return $key->getValue();
        }

        $key = trim($key);

        if (strpos($key, '->') && false === strpos($key, '(')) {
            // JSON字段支持
            list($field, $name) = explode('->', $key, 2);

            return 'json_extract(' . $this->parseKey($query, $field) . ', \'$.' . str_replace('->', '.', $name) . '\')';
        } elseif (strpos($key, '.') && !preg_match('/[,\'\"\(\)`\s]/', $key)) {
            list($table, $key) = explode('.', $key, 2);

            $alias = $query->getOptions('alias');

            if ('__TABLE__' == $table) {
                $table = $query->getOptions('table');
                $table = is_array($table) ? array_shift($table) : $table;
            }

            if (isset($alias[$table])) {
                $table = $alias[$table];
            }
        }

        if ('*' != $key && ($strict || !preg_match('/[,\'\"\*\(\)`.\s]/', $key))) {
            $key = '`' . $key . '`';
        }

        if (isset($table)) {
            if (strpos($table, '.')) {
                $table = str_replace('.', '`.`', $table);
            }

            $key = '`' . $table . '`.' . $key;
        }

        return $key;
    }

    /**
     * 随机排序
     * @access protected
     * @param  Query     $query        查询对象
     * @return string
     */
    protected function parseRand(Query $query)
    {
        return 'rand()';
    }

    /**
     * ON DUPLICATE KEY UPDATE 分析
     * @access protected
     * @param mixed $duplicate
     * @return string
     */
    protected function parseDuplicate($query, $duplicate)
    {
        // 布尔值或空则返回空字符串
        if (is_bool($duplicate) || empty($duplicate)) {
            return '';
        }

        foreach ($duplicate as $key => $val) {
            if (is_numeric($key)) {
                // array('field1', 'field2', 'field3') 解析为 ON DUPLICATE KEY UPDATE field1=VALUES(field1), field2=VALUES(field2), field3=VALUES(field3)
                $updates[] = "`$val`=VALUES(`$val`)";
            } 
        }

        if (empty($updates)) {
            //array('field1' => "a", 'field2' ="b") 解析为 ON DUPLICATE KEY UPDATE field1=:data_field1_duplicate
            $duplicate = $this->parseData($query, $duplicate, [], [], '_duplicate');
            foreach ($duplicate as $key => $val) {
                $updates[] = $key . ' = ' . $val;
            }
        }

        return " ON DUPLICATE KEY UPDATE " . join(', ', $updates);
    }

}
