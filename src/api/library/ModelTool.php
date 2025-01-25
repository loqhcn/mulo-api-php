<?php

namespace mulo\api\library;

use app\mm\model\MuloModel;
use app\mm\model\MuloModelItem;
use mulo\exception\MuloException;
use mulo\facade\Db as MuloFacadeDb;
use think\facade\Env;

/**
 * 模型管理辅助
 * 
 */
class ModelTool
{

    public $userId = '';
    public $accessToken = '';
    public $modelDatas = [];
    public $http = null;
    /** @var string 模型缓存目录 */
    public $modelDir = null;


    function __construct($userId, $accessToken = '')
    {
        // MuloFacadeDb::setConnectConfig();

        $this->userId = $userId;
        $this->accessToken = $accessToken;
    }

    /**
     * 设置缓存目录
     * @todo 目录需要以 `DIRECTORY_SEPARATOR` 结尾
     * 
     */
    function setDir($dir)
    {
        if (!is_dir($dir)) {
            throw new MuloException('api:环境配置异常-缓存文件夹不存在', 0, [
                'dir' => $dir,
            ]);
        }

        $this->modelDir = $dir;
    }

    /**
     * 
     */
    function getHttp()
    {
        if ($this->http) {
            return $this->http;
        }

        $gateway = Env::get('mulo_model.gateway', '');

        $http = \mulo\library\Http::src([
            'baseUrl' => $gateway,
            'headers' => [
                'token' => $this->accessToken
            ]
        ])
            ->setBeforeResponse(function ($res) {
                return $res['data'];
            });
        $this->http = $http;
        return $http;
    }


    /**
     * 初始化应用的数据库
     * @param string $app 应用名称
     * @todo 原理是facade会返回同一个类实例, 这里进行初始化参数, 后续会调用这个配置
     */
    function initAppDb($app)
    {
        $path = root_path('app') . $app . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "database.php";

        if (file_exists($path)) {
            $config = require($path);

            MuloFacadeDb::setConnectConfig($config);
        }
    }


    


    /**
     * 读取应用信息
     * 
     */
    function getApp($appId)
    {
        $row = null;

        return $row;
    }

    public function isUseModelCache()
    {
        $dev = Env::get('mulo_model.dev');
        return !$dev;
    }

    function encodeModelData($modelData)
    {
        return base64_encode(json_encode($modelData, JSON_UNESCAPED_UNICODE));
    }
    function decodeModelData($modelData)
    {
        return json_decode(base64_decode($modelData), true);
    }

    /**
     * TODO 读取模型
     * 
     * @param string $modelName 模型名称
     * 
     */
    function getModel(string $modelName)
    {
        // 读取类缓存
        if (isset($this->modelDatas[$modelName])) {
            return $this->modelDatas[$modelName];
        }

        $cacheFilePath = $this->modelDir  . "{$modelName}.mm";

        # 读取文件缓存
        $useCache = $this->isUseModelCache();
        if ($useCache) {
            if (file_exists($cacheFilePath)) {
                try {
                    $modelData = $this->decodeModelData(file_get_contents($cacheFilePath));
                    if (!isset($this->modelDatas[$modelName])) {
                        $this->modelDatas[$modelName] = $modelData;
                    }
                    return $modelData;
                } catch (\Throwable $th) {
                    throw new MuloException("api:环境配置异常-无法读取模型文件", 0, [
                        'todo' => '请删除后重新下载',
                        'path' => $cacheFilePath,
                        'err' => $th->getMessage(),
                        'path' => $th->getFile(),
                        'line' => $th->getLine(),
                    ]);
                }
            }
        }


        # 加载线上
        $res = $this->getHttp()->post('/mm/service/model/row', [
            'name' => $modelName
        ]);

        if ($res['code'] != 200) {
            throw new MuloException($res['msg'], 0, [
                'api_data' => $res['data'],
            ]);
        }
        $modelData = $res['data']['modelData'];

        # 缓存到本地
        if (!is_writable($this->modelDir)) {
            throw new MuloException("api:环境配置异常-无文件写入权限", 0, [
                'path' => $this->modelDir
            ]);
        }

        file_put_contents($cacheFilePath, $this->encodeModelData($modelData));

        if (!isset($this->modelDatas[$modelName])) {
            $this->modelDatas[$modelName] = $modelData;
        }
        return $modelData;
    }


    function getTimeFields($modelData)
    {
        $fields = [];
        foreach ($modelData['items'] as $key => $item) {
            if ($item['type'] == 'time') {
                $fields[] = $item['name'];
            }
        }
        return $fields;
    }

    function getModelForm() {}



    /**
     * 查询关联的模型列表
     * 
     * 
     */
    function getRelations()
    {

        return [
            'relations_names' => [],
            'relations' => []
        ];
    }

    /**
     * [后台] 读取表格规则
     * 
     */
    function getModelTable() {}
}
