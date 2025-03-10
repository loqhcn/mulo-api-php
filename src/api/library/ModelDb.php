<?php

namespace mulo\api\library;

use mulo\api\library\relation\ModelRelationHelper;
use mulo\facade\Db;
use mulo\api\traits\DefineHook;
use mulo\exception\MuloException;

/**
 * 模型数据查询类
 * 
 */
class ModelDb
{
    use DefineHook;

    public $modelData = null;
    public $where = null;

    // 
    public $fields = null;

    /** @var array 查询条件 [ 'func'=>'xx','params'=>[] ] */
    public $wheres = []; //筛选条件加入

    /** @var array|null 数量筛选(tp的Db->limit的规则) */
    public $limitParams = null;

    public $orderParams = null;

    /** 
     * @var array 筛选 
     * @todo 1,验证user_id
     * 
     */
    public $filters = [];

    public $relations = [];


    function __construct($model)
    {

        $this->modelData = $model;
    }


    /**
     * 设置关联配置
     * 
     */
    function setRelations($relations)
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * 构建链式操作
     * 
     * @param array|string $modelData 模型数据|模型名
     * 
     */
    static function model($modelData)
    {
        if (is_string($modelData)) {
            $modelData = modelTool()->getModel($modelData);
        }

        return new self($modelData);
    }

    /**
     * 加入关联数据
     * 
     */
    function with(array $relationNames)
    {

        return $this;
    }

    /**
     * 设置字段
     * @param string|array $fields 字段
     */
    function field($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * where查询
     */
    function where(...$options)
    {
        // $this->where = $where;
        $this->wheres[] = [
            'func' => 'where',
            'params' => $options,
        ];

        return $this;
    }

    /**
     * 设置筛选规则
     * 
     */
    function filter(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    # SECTION 执行查询


    /**
     * 
     * TODO order 排序规则
     * 
     * @example order("id desc")
     * @example order("id","desc")
     * 
     */
    function order(...$params)
    {
        $this->orderParams = $params;
        return $this;
    }

    /**
     * 
     * TODO limit 查询数量
     * 
     *
     */
    function limit(...$params)
    {
        $this->limitParams = $params;
        return $this;
    }


    # >SECTION 执行查询

    /**
     * 注入当前的查询信息
     * 
     * @return $query
     */
    function getQuery()
    {
        $query =  Db::table($this->modelData['table']);

        $baseAlias = 'c';
        $relationHandle = ModelRelationHelper::src([
            'fields' => $this->fields,
            'wheres' => $this->wheres,
            'filters' => $this->filters,
            'order' => $this->orderParams,
            'limit' => $this->limitParams,
        ])
            ->setRelations($this->relations)
            ->dest();
        // 处理关联数据
        // throw new MuloException('dev',0,[
        //     'relationHandle'=>$relationHandle
        // ]);

        if ($relationHandle['hasRelation']) {
            $this->fields = $relationHandle['fields'];
            $this->wheres = $relationHandle['wheres'];
            $this->filters = $relationHandle['filters'];
            $this->orderParams = $relationHandle['order'];
            $this->limitParams = $relationHandle['limit'];

            $query = $query->alias($baseAlias);
            foreach ($relationHandle['joins'] as $key => $joinParam) {
                $query = $query->join(...$joinParam);
            }
        }




        if ($this->fields) {
            $query = $query->field($this->fields);
        }

        foreach ($this->wheres as $key => $whereRow) {
            $func = $whereRow['func'];
            $params = $whereRow['params'];

            $query = $query->$func(...$params);
        }

        // if ($this->where) {
        //     $query = $query->where($this->where);
        // }

        if (!empty($this->filters)) {
            $filterWhere = BaseFilter::src($this->filters)->dest();
            $query = $query->where($filterWhere);
        }

        if ($this->limitParams && !empty($this->limitParams)) {
            $query = $query->limit(...$this->limitParams);
        }

        if ($this->orderParams && !empty($this->orderParams)) {
            $query = $query->order(...$this->orderParams);
        }

        return $query;
    }

    /**
     * TODO select 读取列表
     * 
     * @todo 查询结果必定返回array
     * 
     * @return array
     */
    function select()
    {
        $query = $this->getQuery();

        $list = $query->select();
        return $list ? $list->toArray() : [];
    }


    /**
     * TODO paginate 读取列表[分页]
     * 
     * @todo 查询结果必定返回array
     * 
     * @return array
     */
    function paginate($psize = 10)
    {
        $query = $this->getQuery();
        $list = $query->paginate($psize);
        return $list;
    }

    /**
     * 
     * TODO find 读取一行
     * 
     */
    function find($id = null)
    {
        $query =  $this->getQuery();

        $row = null;
        if ($id !== null) {
            $row = $query->find($id);
            return $row;
        }

        // throw new MuloException('dev',0,[
        //     'sql'=>$query->fetchSql()->find()
        // ]);

        $row = $query->find();

        return $row;
    }



    /**
     * 
     * TODO sum 获取总分
     * 
     */
    function sum($field)
    {
        $query =  $this->getQuery();
        $row = $query->sum($field);
        return $row;
    }



    /**
     * TODO 保存数据
     * 
     * @return id
     */
    function save($data, $id = 0, $row = [])
    {
        // 处理数据
        unset($data['id']);
        $data = $this->parseSaveFields($data);
        $query =  Db::table($this->modelData['table']);

        // var_dump($data);
        // exit;

        $data = $this->handleHook('api.save', $data);
        if (!$id) {
            // 添加并读取新的一样
            $data = $this->handleSaveData($data, 'add');
            $data = $this->handleHook('api.add', $data);
            $id = $query->insertGetId($data);
        }
        // 更新
        else {
            $data = $this->handleSaveData($data, 'update');
            $data = $this->handleHook('api.update', $data);
            $ret = $query->where('id', $id)
                ->update($data);
        }

        return $id;
    }

    function insertAll(array $insertDatas)
    {
        $query =  Db::table($this->modelData['table']);
        // $insertData = json_decode(json_encode($insertData,JSON_UNESCAPED_UNICODE),true);
        // $sql = \mulo\api\library\database\BuildSql::buildInsertAllQuery($this->modelData['table'], $insertData);
        // throw new MuloException('dev',0,[
        //     'data'=>json_encode($insertData,JSON_UNESCAPED_UNICODE),
        //     'sql'=>$sql,
        //     // 'sql'=>$query->fetchSql()->insertAll($data),
        //     'sql_db'=>\think\facade\Db::table('ep_store_income_item')->fetchSql()->insertAll($insertData),
        // ]);
        // return $query->insertAll($data);
        
        $chagneNum = 0;
        foreach ($insertDatas as $key => $insertData) {
            // 处理数据
            $chagneNum += $query->insert($insertData);
        }
        return $chagneNum;
    }

    /**
     * 导出规则
     * 
     */
    function dest() {}


    /**
     * TODO 新增数据
     * 
     * @return id
     */
    function add($data)
    {
        // 处理数据
        unset($data['id']);



        $data = $this->parseSaveFields($data);



        $query =  Db::table($this->modelData['table']);
        $data = $this->handleSaveData($data, 'add');
        $data = $this->handleHook('api.add', $data);


        $id = $query->insertGetId($data);
        return $id;
    }

    /**
     * 更新数据
     * 
     * @param array $data 更新的数据
     * 
     */
    function update($data)
    {
        $query =  $this->getQuery();

        return $query->update($data);
    }



    /**
     * 增加或减少
     * 
     */
    function setInc($field, $num = 1)
    {
        $query = $this->getQuery();
        $ret = $query->setInc($field, $num);
        return $ret;
    }


    /**
     * 处理保存的数据
     * @todo save|add|update 处理保存的数据
     * @todo 注入新增时间
     * @todo 注入更新时间
     * 
     */
    function handleSaveData($data, $from = 'add', $id = 0)
    {
        // 读取时间字段(部分模型可能没有时间)

        $timeFields = modelTool()->getTimeFields($this->modelData);
        if ($from == 'add') {
            in_array('createtime', $timeFields) && $data['createtime'] = time();
            in_array('updatetime', $timeFields) && $data['updatetime'] = time();
        }

        if ($from == 'update') {
            in_array('updatetime', $timeFields) && $data['updatetime'] = time();
        }
        return $data;
    }

    /**
     * 
     */
    function getTimeFields()
    {
        $fields = [];
        foreach ($this->modelData['items'] as $key => $item) {
            if ($item['type'] == 'time') {
                $fields[] = $item['name'];
            }
        }
        return $fields;
    }

    /**
     * 更新数据
     * 
     * @param array $data 更新的数据
     * 
     */
    function delete($id = null)
    {
        $query =  $this->getQuery();
        return $query->delete($id);
    }

    function count()
    {
        $query =  $this->getQuery();
        return $query->count();
    }

    /**
     * 处理保存字段
     * @清除不存在的字段
     * 
     */
    function parseSaveFields(array $data)
    {

        $names = array_column($this->modelData['items'], 'name');
        //    移除不在列表的数据
        foreach ($data as $key => $value) {
            if (!in_array($key, $names)) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
