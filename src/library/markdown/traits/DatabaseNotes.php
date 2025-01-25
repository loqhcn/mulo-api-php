<?php

declare(strict_types=1);

namespace mulo\library\markdown\traits;

use mulo\library\markdown\Markdown;

trait DatabaseNotes
{
    /**
     * 生成 markdown 格式数据库字典
     * 
     * @param array $tables 数据表格
     * @return string 
     */
    private function databaseToMd($tables)
    {
        $markdown = new Markdown;
        $markdown->title("数据库表字段说明", 2);

        // 分割线
        // $markdown->line();

        // $markdown->title("目录", 3);
        // $dict = [];
        // foreach ($tables as $table) {
        //     $dict[] = $table['Comment'] . '[' . $table['Name'] . ']';
        // }
        // $markdown->ulist($dict, 'link');

        foreach ($tables as $table) {
            // 表名
            $markdown->title($table['Comment'], 3);

            // 表基本信息
            $quote = '表名：' . $table['Name'] . '   存储引擎: ' . $table['Engine'] . '   字符集: ' . $table['Collation'] . '   行格式: ' . $table['Row_format'];
            $markdown->quote($quote);

            $columns = [
                ['field' => 'Field', 'title' => '字段'],
                ['field' => 'Comment', 'title' => '字段名'],
                ['field' => 'Type', 'title' => '类型'],
                ['field' => 'Null', 'title' => '为空'],
                ['field' => 'Default', 'title' => '默认值'],
                ['field' => 'Collation', 'title' => '字符集'],
                ['field' => 'Key', 'title' => '键'],
                ['field' => 'Extra', 'title' => '特性'],
            ];
            $markdown->table($columns, $table['columns']);
        }

        $mdPath = root_path('runtime' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'database') . 'database-' . date('YmdHis') . '.md';
        $markdown->save($mdPath);

        return $mdPath;
    }



    /**
     * 生成 html 格式数据库字典
     * 
     * @param string $mdPath markdown 格式数据库字典
     * @return string 
     */
    private function databaseToHtml($mdPath)
    {
        $md = file_get_contents($mdPath);

        $parsedown = new \Parsedown();
        $html = $parsedown->text($md);

        $htmlPath = root_path('runtime' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'database') . 'database-' . date('YmdHis') . '.html';
        file_put_contents($htmlPath, $html);

        return $htmlPath;
    }



    /**
     * 生成 pdf 格式数据库字典
     * 
     * @param string $htmlPath html 格式数据库字典
     * @return string 
     */
    private function databaseToPdf($htmlPath)
    {
        // 拼接字体
        $header = "<style>* {font-family: \"simsun\"}</style>";
        // 获取 html
        $html = file_get_contents($htmlPath);
        $html = $header . $html;

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $output = $dompdf->output();

        $pdfPath = root_path('runtime' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'database') . 'database-' . date('YmdHis') . '.pdf';
        file_put_contents($pdfPath, $output);

        return $pdfPath;
    }

}
