<?php

namespace mulo\api;

use mulo\api\facade\DataType;
use mulo\api\form\ModelForm;
use mulo\api\list\ModelList;
use mulo\api\library\BaseFilter;
use mulo\api\library\ModelDb;
use mulo\api\library\ModelHandle;
use mulo\api\list\TreeTool;
use mulo\api\logic\data_model\InjectsHandle;
use mulo\exception\MuloException;
use mulo\facade\Db;
use mulo\api\traits\DefineHook;
// use mulo\facade\Db as MuloFacadeDb;

/**
 * 
 * 
 * 
 */
class DataModelApi
{

    use DefineHook;

    public $modelData = null;
    public $where = null; //where 可以是一个函数或者数组

    /** 
     * @var array 筛选 
     * @todo 1,验证user_id
     * 
     */
    public $filters = [];

    public $relations = [];

    /** 
     * @var array 数据所属 
     * @todo 验证数据所属
     * 
     */
    public $belong = [];


    function __construct($model)
    {

        $this->modelData = $model;
    }

    static function model($model)
    {

        return new self($model);
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

    function setRelations($relations)
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * 添加一个筛选规则
     * 
     */
    function addFilter(array $rules)
    {
        if (DataType::isObjectOfJson($rules)) {
            $this->filters[] = $rules;
            return $this;
        }

        foreach ($rules as $key => $rule) {
            $this->filters[] = $rule;
        }
        return $this;
    }

    function where($where)
    {
        $this->where = $where;
        return $this;
    }

    /**
     * 加入关联
     * @param arr $relationFields 
     * 
     */
    function with($relationFields) {}


    /**
     * 读取列表
     * 
     */
    function select()
    {
        $query =  ModelDb::model($this->modelData);
        // $query =  Db::table($this->modelData['table']);
        if ($this->where) {
            $query = $query->where($this->where);
        }
        if (!empty($this->filters)) {
            $filterWhere = BaseFilter::src($this->filters)->dest();
            $query = $query->where($filterWhere);
        }

        $sortField = input("sort_field",'id');
        $sortMode = input("sort_mode",'desc');
        $list = $query->order("{$sortField} {$sortMode}")->select();
        return $list;
    }

    function find()
    {

        $query =  ModelDb::model($this->modelData);
        if ($this->where) {
            $query = $query->where($this->where);
        }

        if (!empty($this->filters)) {
            $filterWhere = BaseFilter::src($this->filters)->dest();
            $query = $query->where($filterWhere);
        }

        $row = $query->find();
        return $row;
    }

    /**
     * 保存数据
     * 
     */
    function save($data, $id = 0, $row = [])
    {
        // 处理数据
        unset($data['id']);
        $data = $this->parseSaveFields($data);

        $data = $this->handleHook('api.save', $data);
        if (!$id) {
            // 添加并读取新的一样
            $data = $this->handleHook('api.add', $data);
            $id = ModelDb::model($this->modelData['table'])->add($data);
        }
        // 更新
        else {
            $data = $this->handleHook('api.update', $data);
            $ret =  ModelDb::model($this->modelData['table'])->where('id', $id)->update($data);
        }

        return $id;
    }

    /**
     * 处理保存字段
     * @清除不存在的字段
     * 
     */
    function parseSaveFields(array $data)
    {

        // throw new MuloException('dev', 0, [
        //     'data' => $data,
        //     'items' => $this->modelData['items'],
        // ]);

        $names = array_column($this->modelData['items'], 'name');
        //    移除不在列表的数据
        foreach ($data as $key => $value) {
            if (!in_array($key, $names)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    function getQuery()
    {
        $query =  ModelDb::model($this->modelData)->setRelations($this->relations);
        return $query;
    }

    /**
     * TODO 分页查询 paginate
     * 
     */
    function paginate($psize = 10)
    {
        $treeOptions = input('tree_options/a', null);

        $query =  $this->getQuery();
        if ($this->where) {
            $query = $query->where($this->where);
        }
        if (!empty($this->filters)) {
            $query = $query->filter($this->filters);
        }
        $_query = clone $query;

        if ($treeOptions) {
            $query->where($treeOptions['pid_field'], 0);
        }

        // TODO -- 查询列表
        $sortField = input("sort_field",'id');
        $sortMode = input("sort_mode",'desc');
        $sortField = $sortField? $sortField : 'id';
        $sortMode = $sortMode? $sortMode : 'desc';

        $list = $query->order("{$sortField} {$sortMode}")->paginate($psize);
        $list = $list ? $list->toArray() : [];

        // TODO -- [tree] 加载下级数据
        if ($treeOptions) {
            $list['data'] = TreeTool::src($_query)
                ->setList($list['data'])
                ->setFields([
                    // 'deep' => 'deep'
                ])
                ->dest(true);
        }

        return $list;
    }

    /**
     * TODO 列表查询 list
     * 
     */
    function list()
    {
        $query =  $this->getQuery();

        // $query =  Db::table($this->modelData['table']);
        if ($this->where) {
            $query = $query->where($this->where);
        }
        if (!empty($this->filters)) {
            $filterWhere = BaseFilter::src($this->filters)->dest();
            $query = $query->where($filterWhere);
        }

        $list = $query->select();
        return $list;
    }


    /**
     * 读取一行&自动处理
     * 
     */
    function getRow($id = null, $throw = true)
    {
        $saveType = input('save_type', null); //保存类型onlyone每人一条
        if ($saveType == 'onlyone') {
            $row = ModelDb::model($this->modelData)->filter($this->filters)->find();
            $row = $this->handleHook('api.getRow', $row);
            return $row;
        }

        // id输入
        if ($id === null) {
            $id = input('id', 0);
            if (!$id) {
                if ($throw) {
                    throw new MuloException("请输入ID", 0, []);
                }
            }
        }

        // 读取
        $row = $this->where(['id' => $id])->find();
        if (!$row) {
            if ($throw) {
                throw new MuloException("未找到行", 0, [
                    'id' => $id,
                    // 'model' => $this->modelData
                ]);
            }
        }


        $row = $this->handleHook('api.getRow', $row);
        return $row;
    }


    /**
     * TODO API 请求处理
     * 
     * @todo 自动从请求参数处理
     * 
     * @param string $api 请求类型
     * - list 列表
     * - paginate 分页列表
     * - row 读取一行
     * - add 添加
     * - edit 编辑
     * - form_rule 表单规则
     * - table_rule 表格规则
     * - list_rule 列表规则
     * 
     * @return all 请求结果
     */
    function handle($api = 'list')
    {
        $modelName = $this->modelData['name'];

        $request = request();
        $request = $this->handleHook('api.handle.before', $request, 'request');
        $filters = input('filters/a', []);

        if (!empty($filters)) {
            $this->addFilter($filters);
        }

        $data = [
            'title' => $this->modelData['row']['title'],
            'modelName' => $this->modelData['name'],
            'api' => $api,
            'filters' => $this->filters,
        ];

        $data = $this->handleHook('api.begin', $data, 'param');

        # TODO -- list 列表查询
        if ($api == 'list') {
            $list = $this->list();
            $data['list'] = $list;
            $data = $this->handleHook('api.handle.list.end', $data);
        }
        # TODO -- paginate 列表查询
        else if ($api == 'paginate') {
            $this->handleHook('api.handle.paginate.begin', $this, null);
            $list = $this->paginate();
            $data['list'] = $list;
            $data = $this->handleHook('api.handle.paginate.end', $data);
        }
        # TODO -- row 单条查询
        else if ($api == 'row') {

            $data['row'] = $this->getRow();

            $data = $this->handleHook('api.handle.row.end', $data);
        }
        # TODO -- find 筛选一条
        else if ($api == 'find') {
            $row = $this->where(function ($query) use ($filters) {
                if (!empty($filters)) {
                    $query->where($filters);
                }
            })->find();

            if (!$row) {
                throw new MuloException("未找到行", 0, $data);
            }
            $data['row'] = $row;
        }

        # TODO -- save 保存 新增或更新[通过ID判断]
        else if ($api == 'save') {
            // 读取数据
            $id = input('id');
            $data['type'] = $id ? 'update' : 'add';

            $saveData = input('post.data/a', []);
            $saveType = input('save_type', null); //保存类型onlyone每人一条

            if (empty($saveData)) {
                throw new MuloException("请输入数据", 0, [
                    'key' => 'post.data',
                    'data' => $saveData
                ]);
            }

            // 每个用户(数据所属)一条的数据
            if ($saveType == 'onlyone') {
                $row_has = ModelDb::model($this->modelData)->filter($this->filters)->find();
                if ($row_has) {
                    $id = $row_has['id'];
                }
            }

            $saveData = $this->handleHook('api.handle.save.before', $saveData);
            // 执行保存
            $_id = $this->save($saveData, $id);

            if (!$id) {
                $data['id'] = $_id;
            }
            // 刷新这一行
            $row = $this->getRow($_id);

            $data['row'] = $row;
            $data = $this->handleHook('api.handle.save.end', $data);
        }
        # TODO -- save_multi 批量保存 新增或更新[通过ID判断]
        else if ($api == 'save_multi') {

            $items = input('items/a', []);
            if (empty($items)) {
                throw new MuloException("请输入数据", 0, [
                    'key' => 'array post.items',
                ]);
            }

            $list_add = [];
            $list_update = [];

            $saves = [];
            foreach ($items as $key => $item) {
                $item = $this->handleHook('api.handle.save.before', $item, 'none');
                $items[$key] = $item;

                if (!$item) {
                    continue;
                }

                if (isset($item['id'])) {
                    $list_update[] = $item;
                } else {
                    $list_add[] = $item;
                }
            }

            // throw new MuloException('dev', 0, [
            //     '$list_add' => $list_add,
            //     '$list_update' => $list_update,
            // ]);

            $add_num = ModelDb::model($this->modelData['table'])->insertAll($list_add);
            $update_num = 0;

            foreach ($list_update as $key => $li) {
                ModelDb::model($this->modelData['table'])->save($li);
            }

            $data['multi_save_status'] = [
                'updates' => count($list_update),
                'update_num' => $update_num,
                'adds' => count($list_add),
                'add_num' => $add_num,
            ];
            $data = $this->handleHook('api.handle.save.end', $data);
        }
        # TODO -- delete 删除
        else if ($api == 'delete') {
            $deleteAccess = $this->handleHook('api.handle.delete.access', false, 'bool');

            if (!$deleteAccess) {
                throw new MuloException("无权操作", 0, [
                    'open-hook' => 'api.handle.delete.access',
                    'data' => $data
                ]);
            }
            $id = input('id');
            $row = $this->getRow($id);

            ModelDb::model($this->modelData)->where(['id' => $id])->delete();
        }
        # TODO -- form_rule 表单规则
        else if ($api == 'form_rule') {
            $useOption = input('use_option', '');
            $res = ModelHandle::src($modelName)
                ->asForm($useOption)
                ->dest();
            // 

            $modelForm = new ModelForm($res['modelData']);
            $formRule = $modelForm->setHooks($this->hooks)->dest();
            $data['form_rule'] = $formRule;

            $data = $this->handleHook('api.handle.form_rule.end', $data);
        }
        # TODO -- table_rule 表格规则
        else if ($api == 'table_rule') {
            $useOption = input('use_option', '');
            $res = ModelHandle::src($modelName)
                ->asTable($useOption)
                ->dest();

            $filterRes = ModelHandle::src($modelName)
                ->asFilter($useOption)
                // ->setItem([])
                ->dest();

            // 处理表格
            $modelList = new ModelList($res['modelData']);
            $listRule = $modelList->setHooks($this->hooks)->dest();

            $data['list_rule'] = $listRule;
            $data['config'] = $res['config'];
            $data['filters_rule'] = $filterRes['items'];

            $data = $this->handleHook('api.handle.table_rule.end', $data);
        }
        # TODO -- view_rule 查看规则
        else if ($api == 'view_rule') {
            $useOption = input('use_option', '');
            $res = ModelHandle::src($modelName)
                ->asView($useOption)
                ->dest();
            $data['config'] = $res['config'];
            $data['rules'] = $res['items'];
            $data = $this->handleHook('api.handle.view_rule.end', $data);
        }
        # TODO -- view_rule 查看规则
        else if ($api == 'detail_rule') {
            $useOption = input('use_option', '');
            $res = ModelHandle::src($modelName)
                ->asDetail($useOption)
                ->dest();
            $data['config'] = $res['config'];
            $data['rules'] = $res['items'];
            $data = $this->handleHook('api.handle.view_rule.end', $data);
        }
        # TODO -- filter_rule 查看筛选
        else if ($api == 'filter_rule') {
            $useOption = input('use_option', '');
            $res = ModelHandle::src($modelName)
                ->asFilter($useOption)
                ->dest();
            $data['config'] = $res['config'];
            $data['rules'] = $res['items'];
            $data = $this->handleHook('api.handle.filter_rule.end', $data);
        }
        # TODO -- list_rule 列表规则
        else if ($api == 'list_rule') {
            $useOption = input('use_option', '');
            $res = ModelHandle::src($modelName)
                ->asList($useOption)
                ->dest();

            $modelForm = new ModelList($res['modelData']);
            $listRule = $modelForm->setHooks($this->hooks)->dest();
            $data['list_rule'] = $listRule;
        }

        // 处理数据注入
        $injects = input('post.injects/a', []);
        if (!empty($injects)) {
            $data = InjectsHandle::src($data)->model($this->modelData)->setInjects($injects)->dest();
        }

        $data = $this->handleHook('api.handle.end', $data);
        return $data;
    }
}
