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

use crmeb\jobs\SyncProductTopJob;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Queue;

class changeHotTop extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('change:hotTop')
            ->setDescription('清楚缓存：php think change:hotTop');
    }

    /**
     * TODO
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     * @author Qinii
     * @day 4/24/22
     */
    protected function execute(Input $input, Output $output)
    {
        Queue::push(SyncProductTopJob::class,[]);
        $output->writeln('执行成功');
    }

}
