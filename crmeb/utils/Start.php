<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace crmeb\utils;

use think\App;

/**
 * Start输出类
 * Class Json
 * @package crmeb\utils
 */
class Start
{
    protected $context = '';
    const LINE = '------------------------------------------------'.PHP_EOL;
    public function show()
    {
        $this->opCacheClear();
        $this->context = $this->logo();
        $this->context .= self::LINE;
        $this->displayItem('php      version', phpversion());
        $this->displayItem('swoole   version', phpversion('swoole'));
        $this->displayItem('swoole_loader version', phpversion('swoole_loader'));
        $this->displayItem('thinkphp version', App::VERSION);
        $this->displayItem('crmeb    version', get_crmeb_version());

        //http配置
        $httpConf = \config("swoole.server");
        $this->displayItem('http host', $httpConf["host"]);
        $this->displayItem('http port', $httpConf["port"]);
        $this->displayItem('http worker_num', $httpConf['options']["worker_num"]);

        //websocket配置
        $this->displayItem('websocket enable', \config("swoole.websocket.enable"));

        //rpc配置
        $rpcConf = \config("swoole.rpc.server");
        $this->displayItem('rpc enable', $rpcConf["enable"]);
        if ($rpcConf["enable"]) {
            $this->displayItem('rpc host', $rpcConf["host"]);
            $this->displayItem('rpc port', $rpcConf["port"]);
            $this->displayItem('rpc worker_num', $rpcConf["worker_num"]);
        }

        //队列配置
        $this->displayItem('queue enable', \config("swoole.queue.enable"));

        //热更新配置
        $this->displayItem('hot_update enable', (bool)\config("swoole.hot_update.enable"));

        //debug配置
        $this->displayItem('app_debug enable', (bool)env("APP_DEBUG"));

        $this->displayItem('time', date('Y-m-d H:i:s'));

        //打印信息
        echo $this->context;
    }


    private function logo()
    {
        return <<<LOGO

   ██████  ███████   ████     ████ ████████ ██████         ████     ████ ████████ ███████  
  ██░░░░██░██░░░░██ ░██░██   ██░██░██░░░░░ ░█░░░░██       ░██░██   ██░██░██░░░░░ ░██░░░░██ 
 ██    ░░ ░██   ░██ ░██░░██ ██ ░██░██      ░█   ░██       ░██░░██ ██ ░██░██      ░██   ░██ 
░██       ░███████  ░██ ░░███  ░██░███████ ░██████   █████░██ ░░███  ░██░███████ ░███████  
░██       ░██░░░██  ░██  ░░█   ░██░██░░░░  ░█░░░░ ██░░░░░ ░██  ░░█   ░██░██░░░░  ░██░░░██  
░░██    ██░██  ░░██ ░██   ░    ░██░██      ░█    ░██      ░██   ░    ░██░██      ░██  ░░██ 
 ░░██████ ░██   ░░██░██        ░██░████████░███████       ░██        ░██░████████░██   ░░██
  ░░░░░░  ░░     ░░ ░░         ░░ ░░░░░░░░ ░░░░░░░        ░░         ░░ ░░░░░░░░ ░░     ░░ 

LOGO;
    }

    private function displayItem($name, $value)
    {
        if ($value === true) {
            $value = 'true';
        }
        elseif ($value === false) {
            $value = 'false';
        }
        elseif ($value === null) {
            $value = 'null';
        }
        $this->context .= "\e[32m" . str_pad($name, 25, ' ', STR_PAD_RIGHT) .'|    '. "\e[34m" . $value . "\e[0m \n";
        $this->context .= self::LINE;
    }

    private function opCacheClear()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
