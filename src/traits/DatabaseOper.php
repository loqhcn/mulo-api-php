<?php

declare(strict_types=1);

namespace mulo\traits;

use mulo\exception\MuloException;
use app\admin\model\config\Config as ConfigModel;
use think\facade\Db;

trait DatabaseOper
{
    /**
     * 获取数据库表详细信息
     */
    public function databaseTables()
    {
        $cacheKey = 'database_db_tables';
        if (cache('?' . $cacheKey)) {
            return cache($cacheKey);
        }

        $tables = Db::query('SHOW TABLE STATUS');

        foreach ($tables as $key => $table) {
            //获取改表的所有字段信息
            $columns = DB::query("SHOW FULL FIELDS FROM `" . $table['Name'] . "`");
            $table['columns'] = $columns;
            $tables[$key] = $table;
        }

        // 缓存 1 小时
        cache($cacheKey, $tables, 3600);

        return $tables;
    }


    /**
     * 获取表注释
     */
    public function tableComment()
    {
        $cacheKey = 'database_db_comment_name';
        if (cache('?' . $cacheKey)) {
            return cache($cacheKey);
        }

        $tables = Db::query('SHOW TABLE STATUS');
        $tableComment = array_column($tables, null, 'Name');

        $newTableComment = [];
        foreach ($tableComment as $key => $comment) {
            $newTableComment[$key] = $comment['Comment'];
        }
        // 缓存 1 小时
        cache($cacheKey, $newTableComment, 3600);

        return $newTableComment;
    }



    /**
     * enable strict mode.
     */
    protected function strictMode()
    {
        $sql_mode = "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";

        Db::execute($sql_mode);
    }
}
