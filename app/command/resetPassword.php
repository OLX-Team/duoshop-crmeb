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

use app\common\repositories\system\admin\AdminRepository;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\console\input\Option;

class resetPassword extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('reset:password')
            ->addArgument('root', Argument::OPTIONAL, 'root : admin')
            ->addOption('pwd', null, Option::VALUE_REQUIRED, 'pwd : 123456')
            ->setDescription('php think reset:password admin --pwd 123');
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
        $account = $input->getArgument('root');
        if ($input->hasOption('pwd')){
            $pwd = $input->getOption('pwd');
        }
        $make = app()->make(AdminRepository::class);
        $accountData = $make->accountByAdmin($account);
        if(!$accountData) {
            $output->warning('管理员账号不存在');
        }else{
            $pwd_ = $make->passwordEncode($pwd);
            $accountData->pwd = $pwd_;
            $accountData->save();
            $output->info('账号：'.$account.'；密码已重置:'.$pwd);
        }
    }
}
