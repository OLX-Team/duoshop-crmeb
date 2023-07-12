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


use app\webscoket\handler\AdminHandler;
use app\webscoket\handler\MerchantHandler;
use app\webscoket\handler\ServiceHandler;
use app\webscoket\handler\UserHandler;
use Swoole\Server;
use Swoole\Websocket\Frame;
use think\Config;
use think\Event;
use think\facade\Cache;
use think\Request;
use think\response\Json;
use think\swoole\Websocket;
use think\swoole\websocket\Room;

/**
 * Class Manager
 * @package app\webscoket
 * @author xaboy
 * @day 2020-04-29
 */
class Manager extends Websocket
{

    /**
     * @var \Swoole\WebSocket\Server
     */
    protected $server;

    /**
     * @var Ping
     */
    protected $pingService;

    /**
     * @var int
     */
    protected $cache_timeout;

    const USER_TYPE = ['admin', 'user', 'mer', 'ser'];

    /**
     * Manager constructor.
     * @param Server $server
     * @param Room $room
     * @param Event $event
     * @param Ping $ping
     * @param Config $config
     */
    public function __construct(Server $server, Room $room, Event $event, Ping $ping, Config $config)
    {
        parent::__construct($server, $room, $event);
        $this->pingService = $ping;
        $this->cache_timeout = (int)($config->get('swoole.websocket.ping_timeout', 60000) / 1000) + 2;
        app()->bind('websocket_handler_admin', AdminHandler::class);
        app()->bind('websocket_handler_user', UserHandler::class);
        app()->bind('websocket_handler_mer', MerchantHandler::class);
        app()->bind('websocket_handler_ser', ServiceHandler::class);
    }

    /**
     * @param int $fd
     * @param Request $request
     * @return mixed
     * @author xaboy
     * @day 2020-05-06
     */
    public function onOpen($fd, Request $request)
    {
        $type = $request->get('type');
        $token = $request->get('token');
        if (!$token || !in_array($type, self::USER_TYPE)) {
            return $this->server->close($fd);
        }
        try {
            $data = $this->exec($type, 'login', compact('fd', 'request', 'token'))->getData();
        } catch (\Exception $e) {
//            var_dump($e->getMessage());
            return $this->server->close($fd);
        }
        if (!isset($data['status']) || $data['status'] != 200 || !($data['data']['uid'] ?? null)) {
//            var_dump($data);
            return $this->server->close($fd);

        }
        $type = array_search($type, self::USER_TYPE);
        $this->login($type, $fd, $data['data']);
        $this->pingService->createPing($fd, time(), $this->cache_timeout);
        return $this->send($fd, app('json')->message('ping', ['now' => time()]));
    }

    public function login($type, $fd, $data)
    {
        $key = '_ws_' . $type;

        Cache::sadd($key, $fd);
        Cache::sadd($key . $data['uid'], $fd);
        Cache::set('_ws_f_' . $fd, [
            'type' => $type,
            'uid' => $data['uid'],
            'fd' => $fd,
            'payload' => $data['payload'] ?? null,
            'mer_id' => $data['mer_id'] ?? null
        ], 3600);

        if (isset($data['mer_id'])) {
            $groupKey = $key . '_group' . $data['mer_id'];
            Cache::sadd($groupKey, $fd);
            Cache::expire($groupKey, 3600);
        }

        $this->refresh($type, $fd, $data['uid']);
    }

    public function refresh($type, $fd, $uid)
    {
        $key = '_ws_' . $type;
        Cache::expire($key, 3600);
        Cache::expire($key . $uid, 3600);
        Cache::expire('_ws_f_' . $fd, 3600);
    }

    public function logout($type, $fd)
    {
        $data = Cache::get('_ws_f_' . $fd);
        $key = '_ws_' . $type;
        Cache::srem($key, $fd);
        if ($data) {
            Cache::delete('_ws_f_' . $fd);
            Cache::srem($key . $data['uid'], $fd);
            if (($data['mer_id'] ?? null) !== null) {
                $groupKey = $key . '_group' . $data['mer_id'];
                Cache::srem($groupKey, $fd);
            }
        }
    }

    public static function merFd($merId)
    {
        return Cache::smembers('_ws_2_group' . $merId) ?: [];
    }

    public static function userFd($type, $uid = '')
    {
        $key = '_ws_' . $type . $uid;
        return Cache::smembers($key) ?: [];
    }

    /**
     * @param $type
     * @param $method
     * @param $result
     * @return null|Json
     * @author xaboy
     * @day 2020-05-06
     */
    protected function exec($type, $method, $result)
    {
        $handler = app()->make('websocket_handler_' . $type);
        if (!method_exists($handler, $method)) return null;
        /** @var Json $response */
        return $handler->{$method}($result);
    }

    /**
     * @param Frame $frame
     * @return bool
     * @author xaboy
     * @day 2020-04-29
     */
    public function onMessage(Frame $frame)
    {
        $info = Cache::get('_ws_f_' . $frame->fd);
        $result = json_decode($frame->data, true) ?: [];

        if (!isset($result['type']) || !$result['type']) return true;
        $this->refresh($info['type'], $frame->fd, $info['uid']);
        if (($info['mer_id'] ?? null) !== null) {
            $groupKey = '_ws_' . $info['type'] . '_group' . $info['mer_id'];
            Cache::expire($groupKey, 3600);
        }
        if ($result['type'] == 'ping') {
            return $this->send($frame->fd, app('json')->message('ping', ['now' => time()]));
        }

        $data = $result['data'] ?? [];
        $frame->uid = $info['uid'];
        $frame->payload = $info['payload'];
        /** @var Json $response */
        $response = $this->exec(self::USER_TYPE[$info['type']], $result['type'], compact('data', 'frame', 'info'));
        if ($response) return $this->send($frame->fd, $response);
        return true;
    }

    protected function send($fd, Json $json)
    {
        $this->pingService->createPing($fd, time(), $this->cache_timeout);
        if ($this->server->isEstablished($fd) && $this->server->exist($fd)) {
            $this->server->push($fd, json_encode($json->getData()));
        }
        return true;
    }

    /**
     * @param int $fd
     * @param int $reactorId
     * @author xaboy
     * @day 2020-04-29
     */
    public function onClose($fd, $reactorId)
    {
        $data = Cache::get('_ws_f_' . $fd);
        if ($data) {
            $this->logout($data['type'], $fd);
            $this->exec(self::USER_TYPE[$data['type']], 'close', $data);
        }
        $this->pingService->removePing($fd);
    }
}
