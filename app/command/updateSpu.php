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

// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\command;

use app\common\repositories\store\product\SpuRepository;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

class updateSpu extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('spu')
            ->addOption('productType', null, Option::VALUE_REQUIRED, 'product type :0,1,2,3')
            ->setDescription('the update spu command');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/15
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     */
    protected function execute(Input $input, Output $output)
    {
        $prodcutType = [];
        if ($input->hasOption('productType')){
            $tyep = $input->getOption('productType');
            if(in_array($tyep,[0,1,2,3,4])) $prodcutType = [$tyep];
        }


        $output->writeln('开始执行');
        $this->checkAndUpdateSpu($prodcutType);
        $output->writeln('执行完成');
    }

    public function checkAndUpdateSpu($prodcutType)
    {
        app()->make(SpuRepository::class)->updateSpu($prodcutType);
    }
}
