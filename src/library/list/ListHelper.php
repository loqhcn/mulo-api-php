<?php

namespace mulo\library\list;


class ListHelper
{

    function __construct() {}

    static function src($list = [])
    {
        $obj = new self();
        if ($list && !empty($list)) {
            $obj->setList($list);
        }

        return $obj;
    }

    function setList($list)
    {
        $this->list = $list;
    }

    function dest()
    {
        

        return $this->list;
    }
}
