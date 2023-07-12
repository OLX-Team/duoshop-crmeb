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


use app\common\repositories\system\merchant\MerchantRepository;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class ClearRedundancy extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('clear:redundancy')
            ->setDescription('已删除的商户的商品相关数据');
    }

    protected function execute(Input $input, Output $output)
    {
        try{
            app()->make(MerchantRepository::class)->clearRedundancy();
        }catch (\Exception $exception){
            Log::info('清除冗余错误：'.$exception->getMessage());
        }
        $output->info('执行成功');
    }

}
