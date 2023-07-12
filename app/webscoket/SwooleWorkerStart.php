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


namespace app\webscoket;


use app\common\repositories\store\service\StoreServiceUserRepository;
use crmeb\interfaces\ListenerInterface;
use Swoole\Server;
use Swoole\Timer;
use think\Config;
use think\facade\Cache;

/**
 * Class SwooleWorkerStart
 * @package app\webscoket
 * @author xaboy
 * @day 2020-04-29
 */
class SwooleWorkerStart implements ListenerInterface
{

    /**
     * @var \Swoole\WebSocket\Server
     */
    protected $server;

    /**
     * @var Config
     */
    protected $config;

    /**
     * SwooleWorkerStart constructor.
     * @param Server $server
     * @param Config $config
     */
    public function __construct(Server $server, Config $config)
    {
        $this->server = $server;
        $this->config = $config;
    }

    /**
     * @param $event
     * @author xaboy
     * @day 2020-04-29
     */
    public function handle($event): void
    {
        if (!env('INSTALLED', false)) return;
        if ($this->server->worker_id == ($this->config->get('swoole.server.options.worker_num')) && $this->config->get('swoole.websocket.enable', false)) {
            $keys = array_merge(Cache::keys('m_chat*'), Cache::keys('u_chat*'));
            if (count($keys)) {
                Cache::del(...$keys);
            }
            $this->ping();
            app()->make(StoreServiceUserRepository::class)->onlineDown();
        }
    }

    /**
     * @author xaboy
     * @day 2020-05-06
     */
    protected function ping()
    {
        /**
         * @var $pingService Ping
         */
        $pingService = app()->make(Ping::class);
        $server = $this->server;
        $timeout = (int)($this->config->get('swoole.websocket.ping_timeout', 60000) / 1000);
        Timer::tick(1500, function (int $timer_id) use (&$server, &$pingService, $timeout) {
            $nowTime = time();
            foreach ($server->connections as $fd) {
                if ($server->isEstablished($fd) && $server->exist($fd)) {
                    $last = $pingService->getLastTime($fd);
                    if ($last && ($nowTime - $last) > $timeout) {
                        $server->close($fd);
                    }
                }
            }
        });
    }
}
