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

use Swoole\Coroutine\MySQL\Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\event\RouteLoaded;
use think\facade\Cache;
use think\facade\Route;
use app\common\repositories\system\auth\MenuRepository;

class clearCache extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('clearCache')
            ->addArgument('cacheType',Argument::OPTIONAL, 'php think menu [1] / [2]')
            ->setDescription('清楚缓存：php think clearCache 1');
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
        $type = $input->getArgument('cacheType');
        $tag = ['sys_login_freeze','mer_login_freeze'];
        $msg = '';
        switch ($type) {
            case 0:
                $msg = '平台登录限制';
                $tag = 'sys_login_freeze';
                break;
            case 1:
                $msg = '商户登录限制';
                $tag = 'mer_login_freeze';
                break;
        }
        Cache::tag($tag)->clear();
        $output->writeln('清楚缓存'.$msg);
    }


}
