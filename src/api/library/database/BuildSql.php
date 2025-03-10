<?php

namespace mulo\api\library\database;

class BuildSql
{
    static function buildInsertAllQuery($tableName, $data)
    {
        if (empty($data)) {
            return '';
        }

        // 获取所有字段名
        $columns = array_keys($data[0]);
        $columnString = implode(', ', $columns);

        $values = [];
        foreach ($data as $row) {
            $escapedValues = [];
            foreach ($row as $value) {
                // 对字符串值进行转义，防止 SQL 注入
                if (is_string($value)) {
                    $escapedValue = addslashes($value);
                    $escapedValues[] = "'$escapedValue'";
                } elseif (is_null($value)) {
                    $escapedValues[] = 'NULL';
                } else {
                    $escapedValues[] = $value;
                }
            }
            $values[] = '(' . implode(', ', $escapedValues) . ')';
        }

        $valueString = implode(', ', $values);

        // 生成最终的 INSERT 语句
        $query = "INSERT INTO $tableName ($columnString) VALUES $valueString";

        return $query;
    }
}
