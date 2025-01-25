<?php

namespace mulo\api\library\storage\common;


/**
 * 
 * 
 * 
 */
class File
{
    /**
     * @var File
     */
    public $file;

    /**
     * @var string 文件sha1编码
     */
    public $sha1 = '';

    /**
     * @param array $file php的表单文件
     * 
     */
    public function __construct($file)
    {
        $this->file = $file;
    }


    /**
     * 获取文件扩展名
     * 
     * @return string
     */
    function getFileExt()
    {
        $fileName = $this->file['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION); // 提取文件后缀名
        return $fileExtension;
    }

    /**
     * 获取文件当前路径
     * 
     * @return string
     */
    function getPath()
    {
        return $this->file['tmp_name'];
    }


    /**
     * 获取文件sha1编码
     * 
     * @return string
     */
    function getFileSha1()
    {
        return sha1_file($this->file['tmp_name']);
    }

    /**
     * 获取文件当前路径
     * 
     * @return string
     */
    function getName()
    {
        return $this->file['name'];
    }
}
