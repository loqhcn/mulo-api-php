<?php

namespace mulo\api\library\relation;

use mulo\api\facade\DataType;
use mulo\exception\MuloException;
use mulo\library\data_type\PhpDataType;

class ModelRelationHelper
{
    // /**
    //  * 生成关联字段
    //  *
    //  * @param   array   $items 字段 
    //  * @param   array   $relation关联

    public $options = [
        'wheres' => [],
        'filters' => [],
        'sorts' => [],
        'fields' => [],
        'with' => [],
        'order' => '',
        'limit' => '',
    ];

    public $baseAlias = 'c';

    public $relations = [];
    public $usedRelations = [];

    function __construct($options)
    {
        if (isset($options['wheres'])) {
            $this->options['wheres'] = $options['wheres'];
        }

        if (isset($options['filters'])) {
            $this->options['filters'] = $options['filters'];
        }

        if (isset($options['sorts'])) {
            $this->options['sorts'] = $options['sorts'];
        }

        if (isset($options['fields'])) {
            $this->options['fields'] = $options['fields'];
        }

        if (isset($options['with'])) {
            $this->options['with'] = $options['with'];
        }
        if (isset($options['order'])) {
            $this->options['order'] = $options['order'];
        }

        if (isset($options['limit'])) {
            $this->options['limit'] = $options['limit'];
        }
    }

    static function src($options = [])
    {
        $obj = new self($options);

        return $obj;
    }
    /**
     * 解析是否有关联数据
     *
     */
    protected function checkHasRelation()
    {

        // throw new MuloException('dev', 0, [
        //     'where' => $this->options['wheres']
        // ]);

        if (!empty($this->options['wheres'])) {
            foreach ($this->options['wheres'] as $key => $whereRow) {
                $func = $whereRow['func'];
                $params = $whereRow['params'];

                if (is_array($params)) {
                    if (DataType::isArrayOfJson($params)) {
                        $testHas = $this->checkNameHasRelation($params[0] ?? '');
                        if ($testHas) {
                            return true;
                        }
                    } else {
                        foreach ($params as $key => $param) {
                            if ($this->checkNameHasRelation($key)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }


        if (!empty($this->options['filters'])) {

            // throw new MuloException('dev',0,[
            //     'msg'=>'数组索引筛选格式错误',
            //     'filters'=>$this->options['filters'],
            // ]);

            foreach ($this->options['filters'] as $key => $li) {
                if (is_array($li)) {
                    if (empty($li)) {
                        continue;
                    }

                    // 数组索引筛选
                    if ($this->isNumberIndexArray($li)) {


                        // 传输传入方式 ["title","=","3"]
                        $testHas = $this->checkNameHasRelation($li[0] ?? '');
                        if ($testHas) {
                            return true;
                        }
                        continue;
                    }

                    foreach ($li as $key => $value) {
                        if ($this->checkNameHasRelation($key)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * 检查是否匹配, 并提取关联名称
     * @param string $name 字段名
     * @param boolean $isPushName 是否压入关联名称
     * 
     */
    function checkField($name, $isPushName = true)
    {
        $isMatch = strpos($name, '.') !== false;
        if ($isMatch && $isPushName) {
            $arr = explode('.', $name);
            $relationName = $arr[0];
            if ($relationName != $this->baseAlias && !in_array($relationName, $this->usedRelations)) {
                $this->usedRelations[] = $relationName;
            }
        }
        return $isMatch;
    }

    function checkNameHasRelation($name)
    {
        if(!is_string($name)){
            return false;
        }
        return strpos($name, '.') !== false;
    }

    /**
     * 纯数字索引数组
     */
    protected function isNumberIndexArray(array $arr)
    {
        foreach (array_keys($arr) as $key) {
            if (!is_int($key)) {
                return false; // 存在字符串键，说明是关联数组
            }
        }
        return true; // 所有键都是整数，说明是索引数组
    }

    function setRelations($relations)
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * 处理查询参数关联
     * @todo 处理参数的关联信息, 参数名称带点的为关联字段
     * @example ['name'=>'xx','params'=>[1,2,3],'goods.title'=>'xx']
     * @example "name='xx' and goods.title='xx' and params in (1,2,3)"
     * @example [["name","=","xx"],["goods.title","=","xx"],["params","in",[1,2,3]]]
     */
    function parseWhere($whereRow)
    {
        if (empty($whereRow)) {
            return $whereRow;
        }

        if (is_array($whereRow)) {

            if (DataType::isArrayOfJson($whereRow)) {
                $result = [];
                foreach ($whereRow as $key => $condition) {
                    if (DataType::isArrayOfJson($condition) && !empty($condition)) {
                        $result[] = $this->parseWhere($condition);
                    }

                    if ($key == 0) {
                        if (!$this->checkField($condition)) {
                            $condition = "{$this->baseAlias}." . $condition;
                        }
                    }

                    $result[] = $condition;
                }
                return $result;
            } else {
                // 处理数组键值对格式，例如 ['name'=>'xx','params'=>[1,2,3],'goods.title'=>'xx']
                $result = [];
                foreach ($whereRow as $field => $value) {
                    if (!$this->checkField($field)) {
                        $field = "{$this->baseAlias}." . $field;
                    }
                    $result[$field] = $value;
                }
                return $result;
            }
        } elseif (is_string($whereRow)) {
            // 处理字符串格式，例如 "name='xx' and goods.title='xx' and params in (1,2,3)"
            $pattern = '/(\b\w+\b)(?=\s*(=|in)\s*)/';
            $replacement = 'c.$1';
            return preg_replace($pattern, $replacement, $whereRow);
        }
        return $whereRow;
    }



    function dest()
    {
        $hasRelation = $this->checkHasRelation();

        if (!$hasRelation) {
            return [
                'hasRelation' => false,
            ];
        }

        // 添加的关联
        $joins = [];

        $wheres = $this->options['wheres'];
        $filters = $this->options['filters'];
        $sorts = $this->options['sorts'];
        $fields = $this->options['fields'];
        $with = $this->options['with'];
        $order = $this->options['order'];
        $limit = $this->options['limit'];

        # TODO -- fields
        $_fields = [];
        if (!$fields || empty($fields)) {
            $_fields = "{$this->baseAlias}.*";
        } else {
            $fieldsArr = $fields;
            $isString = false;
            if (is_string($fields)) {
                $isString = true;
                $fieldsArr = explode(',', $fields);
            }
            foreach ($fieldsArr as $key => $field) {
                if (!$this->checkField($field)) {
                    $field = "{$this->baseAlias}." . $field;
                }
            }
            $_fields = $isString ? implode(',', $fieldsArr) : $fieldsArr;
        }

        # TODO -- wheres
        $_wheres = [];
        foreach ($wheres as $key => $whereRow) {
            $func = $whereRow['func'];
            $params = $whereRow['params'];

            $params = $this->parseWhere($params);

            $_wheres[] = [
                'func' => $func,
                'params' => $params,

            ];
        }

        # TODO -- filters
        $_filters = [];
        foreach ($filters as $key => $filtersRow) {
            $_filters[] = $this->parseWhere($filtersRow);
        }

        # TODO -- limit
        $_limit = '';
        if ($limit) {
            $limitArr = [];
            $isArr = true;
            if (count($limit) == 1 && is_string($limit[0])) {
                $isArr = false;
                $limitArr =  explode(' ', $limit[0]);
            } else {
                $limitArr = $limit;
            }

            if (!$this->checkField($limitArr[0])) {
                $limitArr[0] = "{$this->baseAlias}." . $limitArr[0];
            }

            $_limit = $isArr ? $limitArr : [implode(' ', $limitArr)];
        }

        # TODO -- order
        $_order = '';
        if ($order) {
            $orderArr = [];
            $isArr = true;
            if (count($order) == 1 && is_string($order[0])) {
                $isArr = false;
                $orderArr =  explode(' ', $order[0]);
            } else {
                $orderArr = $order;
            }

            if (!$this->checkField($orderArr[0])) {
                $orderArr[0] = "{$this->baseAlias}." . $orderArr[0];
            }
            $_order = $isArr ? $orderArr : [implode(' ', $orderArr)];
        }


        // throw new MuloException('dev', 0, [
        //     'usedRelations' => $this->usedRelations,
        //     'relations' => $this->relations,
        //     'order' => $order,
        //     '_order' => $_order,
        //     'limit' => $limit,
        // ]);

        foreach ($this->usedRelations as $key => $relationName) {

            $relationRow = PhpDataType::arrayFind($this->relations, function ($value, $index) use ($relationName) {
                return $value['name'] == $relationName;
            });
            if (!$relationRow) {
                throw new MuloException('关联关系不存在:' . $relationName, 0, [
                    'relationName' => $relationName,
                    'relations' => $this->relations,
                ]);
            }

            $joins[] = [
                [$relationRow['model'] => $relationName],
                "{$this->baseAlias}.{$relationRow['local_field']}={$relationName}.{$relationRow['relation_field']}",
                "left"
            ];
        }

        return [
            'hasRelation' => $hasRelation,
            'relations' => $this->relations,
            'usedRelations' => $this->usedRelations,
            'wheres' => $_wheres,
            'order' => $_order,
            'limit' => $_limit,
            'filters' => $_filters,
            'joins' => $joins,
            'fields' => $_fields,
        ];
    }
}
