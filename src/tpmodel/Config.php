<?php

declare(strict_types=1);

namespace mulo\tpmodel;

class Config extends Common
{
    /**
     * 主键
     */
    protected $pk = 'code';

    protected $name = 'config';

    /**
     * 获取配置组
     *
     * @param string $code
     * @param boolean $cache
     * @return array
     */
    public static function getConfigs($code, $cache = true)
    {
        // 从缓存中获取
        if ($cache) {
            $config = cache("config:{$code}");
            if (empty($config)) {
                $config = self::getConfigs($code, false);
            }
        }

        // 从数据库中查找
        if (!$cache) {
            $row = self::where('code', $code)->find();
            if(!$row) return null;
            if($row['type'] !== 'group') {
                $config = $row->value;
            }else {
                $config = [];
                $list = self::where('parent_code', $code)->select();
                foreach ($list as &$row) {
                    if ($row['type'] === 'group') {
                        $row->value = self::getConfigs($row->code, false);
                    } else {
                        cache("config:{$row->code}", $row->value);
                    }
                    $config[self::getShortCode($row)] = $row->value;
                }
            }
            // 设置配置缓存
            cache("config:{$code}", $config);
        }

        return $config;
    }

    /**
     * 获取单一配置项
     *
     * @param string $code
     * @param boolean $cache
     * @return mixed
     */
    public static function getConfigField($code, $cache = true)
    {
        // 从缓存中获取
        if ($cache) {
            $config = cache("config:{$code}");
            if (empty($config)) {
                $config = self::getConfigField($code, false);
            }
        }

        // 从数据库中查找
        if (!$cache) {
            $config = self::where('code', $code)->value('value');
            // 设置配置缓存
            cache("config:{$code}", $config);
        }

        return $config;
    }

    private static function getShortCode($config)
    {
        if (!empty($config['parent_code'])) {
            return str_replace("{$config['parent_code']}.", "", $config['code']);
        }
        return $config['code'];
    }

    /**
     * 更新配置
     *
     * @param string $code
     * @param array $configParams
     * @return void
     */
    public static function setConfigs(string $code, array $configParams)
    {
        foreach ($configParams as $configKey => $configValue) {
            self::setConfigField($code . '.' . $configKey, $configValue);
        }
        
        self::getConfigs(explode('.', $code)[0], false);
        return self::getConfigs($code);
    }

    /**
     * 更新配置项
     */
    private static function setConfigField($code, $value)
    {
        $config = self::where('code', $code)->find();

        if ($config) {
            if ($config['type'] === 'group') {
                foreach ($value as $k => $v) {
                    self::setConfigField($code . '.' . $k, $v);
                }
            } else {
                $config->value = $value;
                $config->save();
            }
        }
    }


    /**
     * 修改器 数据的保存格式
     *
     * @param string|array $value
     * @param array $data
     * @return string
     */
    public function setValueAttr($value, $data)
    {
        switch ($data['type']) {
            case 'array':
                $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                break;
        }

        return $value;
    }


    /**
     * 获取器，选项
     *
     * @param string|array $value
     * @param array $data
     * @return array
     */
    public function getStoreRangeAttr($value, $data)
    {
        return $this->attrFormatJson($value, $data, 'store_range');
    }



    /**
     * 获取器，返回的格式
     *
     * @param string|array $value
     * @param array $data
     * @return array
     */
    public function getValueAttr($value, $data)
    {
        $value = $value ?: ($data['value'] ?? null);

        switch ($data['type']) {
            case 'array':
                $value = $this->attrFormatJson($value, $data, 'value', true);
                break;
            case 'boolean':
                $value = intval($value) ? 1 : 0;
                break;
            case 'int':
                $value = intval($value);
                break;
            case 'float':
                $value = floatval($value);
                break;
        }

        return $value;
    }
}
