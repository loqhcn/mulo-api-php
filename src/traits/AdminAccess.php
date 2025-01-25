<?php

declare(strict_types=1);

namespace mulo\traits;

use mulo\exception\MuloException;
use mulo\library\Tree;
use app\admin\model\auth\Role as RoleModel;
use app\admin\model\auth\Admin as AdminModel;
use mulo\tpmodel\auth\Admin as BaseAdmin;
use app\admin\model\auth\Access as AccessModel;

trait AdminAccess
{

    /**
     * 获取当前管理员的所有权限
     * @param  bool  $useCache 是否从缓存读取
     * @return array
     */
    public function getAdminAccess(BaseAdmin $admin, bool $useCache = true)
    {
        $roleId = $admin->role_id;

        $access = $this->getAccessByRoleId($roleId, $useCache);
        return $access;
    }

    /**
     * 获取角色组的所有权限
     * @param  bool  $useCache 是否从缓存读取
     * @return array
     */
    public function getAccessByRoleId($roleId, bool $useCache = true)
    {
        if ($useCache) {
            $access = cache("admin.role.{$roleId}");
            if (empty($access)) {
                $access = $this->getAccessByRoleId($roleId, false);
            }
        } else {
            $rules = $this->getRulesByRoleId($roleId);
            $order = 'weigh desc, id asc';
            $menu = (new Tree(function () use ($rules, $order) {
                return (new AccessModel)->whereIn('id', $rules)->where('type', '<>', 'api')->where('status', 'show')->order($order);
            }))->getTree(0);

            $permission = AccessModel::whereIn('id', $rules)->where('type', '<>', 'menu')->where('status', '<>', 'disabled')->order($order)->column('name');
            $access = [
                'menu' => $menu->toArray(),
                'permission' => $permission
            ];
            cache("admin.role.{$roleId}", $access);
        }
        return $access;
    }


    /**
     * 获取指定管理员的所有权限规则ids
     * @param  id 
     * @return void
     */
    public function getRulesByRoleId($id)
    {
        if ($id == 1) {
            return AccessModel::column('id');
        }
        $role = RoleModel::normal()->find($id);
        if ($role) {
            $rules = $role->rules;
        } else {
            $rules = [];
        }

        return $rules;
    }

    /**
     * 当前管理员角色的所有下级角色
     * @param boolean $self     是否包含自己
     * @return array
     */
    public function getChildRoleIdsByRole($role_id, $self = true)
    {
        $role = RoleModel::find($role_id);
        $childRoleIds = (new Tree(new RoleModel))->getChildIds($role->id, $self);
        return array_values(array_filter($childRoleIds));
    }


    /**
     * 当前管理员角色的所有下级角色中的所有管理员的 ids
     * @param boolean $self     是否包含自己
     * @return void
     */
    public function getChildAdminIdsByAdmin(BaseAdmin $admin, $self = true)
    {
        $childAdminIds = $self ? [$admin->id] : [];

        // 获取所有下级角色的ids
        $childRoleIds = $this->getChildRoleIdsByRole($admin->role_id, false);
        $adminIds = AdminModel::whereIn('role_id', $childRoleIds)->column('id');
        if ($adminIds) {
            $childAdminIds = array_merge($childAdminIds, $adminIds);
        }

        return $childAdminIds;
    }
}
