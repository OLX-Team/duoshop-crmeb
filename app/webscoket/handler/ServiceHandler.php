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


namespace app\webscoket\handler;


use app\common\repositories\store\service\StoreServiceLogRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use app\common\repositories\store\service\StoreServiceUserRepository;
use crmeb\services\JwtTokenService;
use crmeb\services\SwooleTaskService;
use Firebase\JWT\ExpiredException;
use Swoole\Server;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Cache;
use Throwable;

/**
 * Class UserHandler
 * @package app\webscoket\handler
 * @author xaboy
 * @day 2020-04-29
 */
class ServiceHandler
{
    /**
     * @var Server
     */
    protected $server;

    /**
     * UserHandler constructor.
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @param array $data
     * @return mixed
     * @author xaboy
     * @day 2020-05-06
     */
    public function test(array $data)
    {
        return app('json')->success($data);
    }

    /**
     * @param array $data
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-06
     */
    public function login(array $data)
    {
        $token = $data['token'] ?? '';
        if (!$token) return app('json')->message('err_tip', '登录过期');

        $repository = app()->make(StoreServiceRepository::class);
        $service = new JwtTokenService();
        try {
            $payload = $service->parseToken($token);
        } catch (ExpiredException $e) {
            $repository->checkToken($token);
            $payload = $service->decode($token);
        } catch (Throwable $e) {//Token 过期
            return app('json')->message('err_tip', '登录过期');
        }
        if ('service' != $payload->jti[1])
            return app('json')->message('err_tip', '登录过期');
        $service = $repository->get($payload->jti[0]);

        if (!$service)
            return app('json')->message('err_tip', '账号不存在');
        if (!$service['is_open'])
            return app('json')->message('err_tip', '账号已被禁用');
        $this->switchServiceChat($service->uid, $data['fd'], 0, 0);
        return app('json')->success(['uid' => $service->uid, 'payload' => [$service->service_id], 'data' => $service->toArray()]);
    }

    /**
     * @param array $data
     * @return mixed
     * @author xaboy
     * @day 2020-05-06
     */
    public function uid(array $data)
    {
        return app('json')->success(['uid' => $data['frame']->uid]);
    }

    public function closeServiceChat($uid, $fd)
    {
        $lst = Cache::sMembers('m_chat' . $uid) ?: [];
        foreach ($lst as $key) {
            if (strpos($key, $fd . '/') === 0) {
                Cache::srem('m_chat' . $uid, $key);
            }
        }
    }

    public function service_chat_end(array $result)
    {
        $this->closeServiceChat($result['frame']->uid, $result['frame']->fd);
    }

    public function service_chat_start(array $result)
    {
        $this->switchServiceChat($result['frame']->uid, $result['frame']->fd, $result['data']['mer_id'] ?? 0, $result['data']['uid']);
    }


    public function switchServiceChat($uid, $fd, $merId, $toUid)
    {
        $this->closeServiceChat($uid, $fd);
        Cache::sadd('m_chat' . $uid, $fd . '/' . $merId . '/' . $toUid);
    }


    /**
     * 商户给用户发
     * @param array $result
     * @return \think\response\Json
     */
    public function service_chat(array $result)
    {
        $data = $result['data'];
        $frame = $result['frame'];
        if (!isset($data['msn_type']) || !isset($data['msn']) || !isset($data['uid']) || !isset($data['mer_id']))
            return app('json')->message('err_tip', '数据格式错误');
        if (!$data['msn']) return app('json')->message('err_tip', '请输入发送内容');
        if (!in_array($data['msn_type'], [1, 2, 3, 4, 5, 6, 7, 8, 100]))
            return app('json')->message('err_tip', '消息类型有误');
        $service = app()->make(StoreServiceRepository::class)->get($frame->payload[0]);
        if (!$service || !$service['status'] || !$service['is_open'])
            return app('json')->message('err_tip', '没有权限');
        $storeServiceLogRepository = app()->make(StoreServiceLogRepository::class);
        if ($service->mer_id && !$storeServiceLogRepository->issetLog($data['uid'], $service->mer_id))
            return app('json')->message('err_tip', '不能主动发送给用户');

        $this->switchServiceChat($frame->uid, $frame->fd, $service->mer_id, $data['uid']);

        $data['msn'] = trim(strip_tags(htmlspecialchars_decode($data['msn'])));
        $data['mer_id'] = $service->mer_id;
        $data['service_id'] = $service->service_id;
        $data['send_type'] = 1;
        try {
            $storeServiceLogRepository->checkMsn($service->mer_id, $data['uid'], $data['msn_type'], $data['msn']);
        } catch (ValidateException $e) {
            return app('json')->message('err_tip', $e->getMessage());
        }
        if ($data['msn_type'] == 100) {
            if (!$storeServiceLogRepository->query(['service_log_id' => $data['msn']])
                ->where('create_time', '>', date('Y-m-d H:i:s', strtotime('- 120 seconds')))->where('mer_id', $data['mer_id'])->where('send_type', 1)->count()) {
                return app('json')->message('err_tip', '消息不能撤回');
            }
        }
        $log = $storeServiceLogRepository->create($data);
        if ($data['msn_type'] == 100) {
            $storeServiceLogRepository->query(['service_log_id' => $data['msn']])->delete();
        }
        app()->make(StoreServiceUserRepository::class)->updateInfo($log, false);
        $storeServiceLogRepository->getSendData($log);
        $log->set('service', $service->visible(['service_id', 'avatar', 'nickname'])->toArray());
        $log = $log->toArray();
        $log['send_time'] = strtotime($log['create_time']);
        $log['send_date'] = date('H:i',strtotime($log['create_time']));
        SwooleTaskService::chatToUser([
            'uid' => $data['uid'],
            'data' => $log,
            'except' => [$frame->fd]
        ]);

        return app('json')->message('chat', $log);
    }

    public function close($result)
    {
        $this->closeServiceChat($result['uid'], $result['fd']);
    }

}
