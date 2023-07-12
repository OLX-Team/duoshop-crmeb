<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------


namespace app\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class ClearMerchantData extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('clear:merchant')
            ->setDescription('清空数据(除系统配置以外的所有数据)');
    }

    protected function execute(Input $input, Output $output)
    {
        $flag = $output->confirm($input, '清空数据前务必做好数据库的备份,防止数据被误删 !!!', false);
        if (!$flag) return;
        $tables = Db::query('SHOW TABLES FROM ' . env('database.database', ''));
        $pre = env('database.prefix', '');
        $bakTables = [
            $pre . 'page_link',
            $pre . 'page_category',
            $pre . 'diy',
            $pre . 'city_area',
            $pre . 'express',
            $pre . 'system_admin',
            $pre . 'system_city',
            $pre . 'system_config',
            $pre . 'system_config_classify',
            $pre . 'system_config_value',
            $pre . 'system_group',
            $pre . 'system_group_data',
            $pre . 'system_menu',
            $pre . 'system_role',
            $pre . 'template_message',
            $pre . 'system_notice_config',
            $pre . 'cache',
        ];

        foreach ($tables as $table) {
            $name = array_values($table)[0];
            if (!in_array($name, $bakTables)) {
                Db::table($name)->delete(true);
            }
        }
        Db::table( $pre . 'cache')->whereNotIn('key','copyright_context,copyright_image,copyright_status')->delete(true);
        $output->info('删除成功');
    }

}
