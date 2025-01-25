<?php

declare(strict_types=1);

namespace mulo\library\markdown;

use mulo\exception\MuloException;

class Markdown
{

    protected $content;

    public function __construct()
    {
    }


    /**
     * 设置内容
     * 
     * @param string $content 要设置的内容
     * @param string $mode cover|append    覆盖模式|追加模式
     * @return $this
     */
    public function setContent($content, $mode = 'cover') {
        if ($mode == 'cover') {
            $this->content = $content;
        } else {
            $this->content .= $content;
        }

        return $this;
    }



    /**
     * 获取内容
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * 创建标题
     * 
     * @param string $title 要设置的内容
     * @param string $level 标题权重，1-6
     * @return $this
     */
    public function title($title = '', $level = 3, $type = '')
    {
        if ($title) {
            if ($type == 'link') {
                $this->content .= '<h' . $level . ' id="' . $title . '">' . $title . '</h' . $level . '>' . PHP_EOL . PHP_EOL;
            } else {
                $this->content .= str_repeat('#', $level) . ' ' . $title . PHP_EOL . PHP_EOL;
            }
        }

        return $this;
    }


    /**
     * 字体加粗
     * 
     * @param string $content 要设置的内容
     * @param string $mode cover|append    覆盖模式|追加模式
     * @return $this|string
     */
    public function strong($content, $mode = 'append') {
        $content = '**' . $content . '**';

        if ($mode == 'append') {
            $this->content .= $content . PHP_EOL;
            return $this;
        }
        
        return $content;
    }


    /**
     * 斜体
     * 
     * @param string $content 要设置的内容
     * @param string $mode cover|append    覆盖模式|追加模式
     * @return $this|string
     */
    public function italic($content, $mode = 'append') {
        $content = '*' . $content . '*';

        if ($mode == 'append') {
            $this->content .= $content . PHP_EOL;
            return $this;
        }

        return $content;
    }


    /**
     * 粗体 + 斜体
     * 
     * @param string $content 要设置的内容
     * @param string $mode cover|append    覆盖模式|追加模式
     * @return $this|string
     */
    public function storageItalic($content, $mode = 'append') {
        $content = '***' . $content . '***';

        if ($mode == 'append') {
            $this->content .= $content . PHP_EOL;
            return $this;
        }

        return $content;
    }


    /**
     * 删除线 line-through
     * 
     * @param string $content 要设置的内容
     * @param string $mode cover|append    覆盖模式|追加模式
     * @return $this|string
     */
    public function throughLine($content, $mode = 'append') {
        $content = '~~' . $content . '~~';

        if ($mode == 'append') {
            $this->content .= $content . PHP_EOL;
            return $this;
        }

        return $content;
    }



    /**
     * 图片
     * 
     * @param string $url
     * @param string $name
     * @return $this
     */
    public function image($url, $name = '')
    {
        $this->content = '![' . ($name ? : '图片') . '](' . $url . ')' . PHP_EOL;

        return $this;
    }


    /**
     * 链接
     * 
     * @param string $url
     * @param string $name
     * @return $this
     */
    public function link($url, $name = '')
    {
        $this->content = '[' . ($name ? : '链接') . '](' . $url . ')' . PHP_EOL;

        return $this;
    }



    /**
     * 无序列表
     * @param array $lists 列表
     * @return $this
     */
    public function ulist($lists, $type = '')
    {
        $current = '';
        foreach ($lists as $list) {
            if ($type == 'link') {
                $current .= '* [' . $list . '](#' . $list . ')' . PHP_EOL;
            } else {
                $current .= '* ' . $list . PHP_EOL;
            }
        }

        $this->content .= $current . PHP_EOL;

        return $this;
    }


    /**
     * 有序列表
     * @param array $lists 列表
     * @return $this
     */
    public function olist($lists)
    {
        $current = '';
        $lists = array_values($lists);

        foreach ($lists as $key => $list) {
            $current .= ($key + 1) . '. ' . $list . PHP_EOL;
        }

        $this->content .= $current . PHP_EOL;

        return $this;
    }


    /**
     * 代码块
     * @param string $code 代码块
     * @return $this
     */
    public function code($code)
    {
        $this->content .= '```' . PHP_EOL;

        $this->content .= $code;

        $this->content .= '```' . PHP_EOL;

        return $this;
    }


    /**
     * 代码单行
     * @param string $code 代码
     * @return $this
     */
    public function codeLine($code)
    {
        $this->content .= '`' . $code . '`' . PHP_EOL;
        return $this;
    }
    

    /**
     * 引用
     * 
     * @param string $content
     * @return $this
     */
    public function quote($content)
    {
        $this->content .= '> ' . $content . PHP_EOL . PHP_EOL;

        return $this;
    }


    /**
     * 分割线
     */
    public function line()
    {
        $this->content .= '---' . PHP_EOL;
    }

    
    /**
     * 表格
     * 
     * @param array $columns    表头
     * @param array $data   表格数据
     * @return $this
     */
    public function table($columns, $data)
    {
        $headers = array_column($columns, 'title');
        $header = '';
        $division = '';
        foreach ($headers as $key => $header_name) {
            $header .= $header_name . ' | ';
            $division .= '--- | ';
        }

        $this->content .= $this->rtrim($header, '| ') . PHP_EOL;
        $this->content .= $this->rtrim($division, '| ') . PHP_EOL;
        
        $table = '';
        foreach ($data as $key => $da) {
            $current = '';
            foreach ($columns as $k => $col) {
                $formatter = $col['formatter'] ?? null;
                if ($formatter && $formatter instanceof \Closure) {
                    // 回调函数的三个参数，value, row, field
                    $current .= $col['formatter']($da[$col['field']], $da, $col['field']) . " | ";
                } else {
                    $current .= $da[$col['field']] . " | ";
                }
            }

            $table .= $this->rtrim($current, '| ') . PHP_EOL;
        }

        $this->content .= $table . PHP_EOL;

        return $this;
    }


    /**
     * 保存
     * 
     * @param string $filename  保存地址
     * @return $this
     */
    public function save($filename)
    {
        // 创建目录
        @mkdir(dirname($filename), 0755, true);

        // 写入文件
        file_put_contents($filename, $this->content);

        return $this;
    }


    /**
     * 去除末尾字符
     * 
     * @param string $string 要处理的字符串
     * @param string $trim  要处理的字符
     * @param string $mode one 只去除一次，all ，重复的全部去除
     */
    private function rtrim($string, $trim, $mode = 'one')
    {
        if ($mode == 'one') {
            return substr($string, 0, strlen($string) - strlen($trim));
        }

        return rtrim($string, $trim);
    }
}
