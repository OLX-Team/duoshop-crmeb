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


namespace crmeb\listens;


use crmeb\interfaces\ListenerInterface;
use crmeb\services\TimerService;
use Swoole\Process;
use Swoole\Server;
use Symfony\Component\Process\PhpExecutableFinder;

class QueueListen extends TimerService implements ListenerInterface
{
    public function handle($event): void
    {
        $process = new Process(function (Process $process) {
            $process->exec((new PhpExecutableFinder)->find(false), [
                dirname(__DIR__, 2) . '/think', 'queue:listen', '--tries=2', '--queue=' . env('queue_name', 'default')
            ]);
        }, false, 0, true);
        app()->make(Server::class)->addProcess($process);
    }
}
