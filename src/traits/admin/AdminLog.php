<?php

declare(strict_types=1);

namespace mulo\traits\admin;

use app\admin\model\auth\AdminLog as AdminLogModel;
use app\admin\model\auth\Access as AccessModel;
use mulo\facade\Auth;
use mulo\library\Tree;
use think\helper\Str;

trait AdminLog
{
    protected $description = null;

    /**
     * 特殊 url
     */
    protected $specialUrl = [
        '/admin/index/login' => '登录',
    ];

    /**
     * 忽略的 url
     */
    protected $ignoreUrl = [
        '/admin/index/loginConfig',
        '/admin/index/captcha',
        '/admin/index/init',
        '/admin/install',
        '/admin/install/check',
        '/admin/install/install'
    ];


    /**
     * 设置描述内容
     *
     * @param [type] $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }


    public function record()
    {
        $url = request()->baseUrl();

        if (in_array($url, $this->ignoreUrl)) {
            return false;
        }

        $admin = Auth::guard('admin')->user();

        $params = request()->param('', null, 'trim,strip_tags,htmlspecialchars');
        $params = $this->getPureContent($params);

        // 获取描述内容
        $description = $this->description;
        if (!$description) {
            $description = $this->specialUrl[$url] ?? null;

            if (!$description) {
                $root = substr(request()->root(), 1);
                $controller = strtolower(request()->controller());
                $action = request()->action();
                $access_name = "{$root}.{$controller}.{$action}";

                $access = AccessModel::where('name', $access_name)->find();
                if ($access) {
                    $accessTitles = (new Tree(new AccessModel))->getParentFields($access, 'title');
                    $description = implode('/', $accessTitles);
                }
            }

            if (!$description) {
                $description = $url;
            }
        }

        $adminLog = new AdminLogModel();
        $adminLog->admin_id = $admin ? $admin->id : 0;
        $adminLog->account = $admin ? $admin->account : 0;
        $adminLog->nickname = $admin ? $admin->nickname : 0;
        $adminLog->url = substr($url, 0, 255);
        $adminLog->params = !is_scalar($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : $params;
        $adminLog->description = $description;
        $adminLog->ip = request()->ip();
        $adminLog->useragent = substr(request()->server('HTTP_USER_AGENT'), 0, 255);

        $adminLog->save();

        return true;
    }

    /**
     * 获取已屏蔽关键信息的数据
     * @param $content
     * @return false|string
     */
    protected function getPureContent($content)
    {
        if (!is_array($content)) {
            return $content;
        }
        foreach ($content as $index => &$item) {
            if (preg_match("/(password|salt|token)/i", strval($index))) {
                $item = "***";
            } else {
                if (is_array($item)) {
                    $item = self::getPureContent($item);
                }
            }
        }
        return $content;
    }
}
