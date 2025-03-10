<?php

namespace mulo\api\data_model\hooks;

/**
 * 
 * 
 * 
 */
class HookConst
{


    # SECTION save

    /**
     * @var string 保存前
     * @todo [添加][修改][删除]保存的字段
     */
    const save_before = 'api.handle.save.before';


    # !SECTION




    # SECTION list




    # !SECTION


    # SECTION form_rule


    /**
     * @var string 模型规则[一行]
     * @todo 修改模型规则
     * @todo 删除规则
     * @todo 设置渲染参数[enum]
     * 
     */
    const form_rule_dest_item = 'api.handle.form_rule.dest.item';

     /**
     * @var string 模型规则
     * @todo 修改模型规则
     * @todo 删除规则
     * @todo 设置渲染参数[enum]
     * 
     */
    const form_rule_end = 'api.handle.form_rule.end';


    # !SECTION


    # SECTION table_rule

    /**
     * @var string 表单一行的模型规则
     * @todo 修改模型规则
     * @todo 删除规则
     * @todo 设置渲染参数[enum]
     * 
     */
    const table_rule_end = 'api.handle.table_rule.end';


    # !SECTION

    /**
     * @var string 删除权限
     * @todo 删除前判断权限
     */
    const delete_access = 'api.handle.delete.access';

    /**
     * @var string 删除后
     * @todo 删除后的操作
     */
    const delete_after = 'api.handle.delete.after';
}
