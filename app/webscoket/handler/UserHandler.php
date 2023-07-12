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
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserRepository;
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
class UserHandler
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
        if ($token && strpos($token, 'Bearer') === 0)
            $token = trim(substr($token, 6));
        if (!$token) return app('json')->message('err_tip', '登录过期');

        /**
         * @var UserRepository $repository
         */
        $repository = app()->make(UserRepository::class);
        $service = new JwtTokenService();
        try {
            $payload = $service->parseToken($token);
        } catch (ExpiredException $e) {
            $repository->checkToken($token);
            $payload = $service->decode($token);
        } catch (Throwable $e) {//Token 过期
            return app('json')->message('err_tip', '登录过期');
        }
        if ('user' != $payload->jti[1])
            return app('json')->message('err_tip', '登录过期');

        $user = $repository->get($payload->jti[0]);
        if (!$user)
            return app('json')->message('err_tip', '账号不存在');
        if (!$user['status'])
            return app('json')->message('err_tip', '账号状态已关闭');

        return app('json')->success(['uid' => $user->uid, 'data' => $user->toArray()]);
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

    public static function serviceOnline($uid, $toUid)
    {
        $lst = Cache::sMembers('m_chat' . $uid) ?: [];
        foreach ($lst as $key) {
            if (strpos($key, '/' . $toUid) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function userOnline($uid, $merId)
    {
        $lst = Cache::sMembers('u_chat' . $uid) ?: [];
        foreach ($lst as $key) {
            if (strpos($key, '/' . $merId) !== false) {
                return true;
            }
        }
        return false;
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

    public function closeChat($uid, $fd)
    {
        $lst = Cache::sMembers('u_chat' . $uid) ?: [];
        foreach ($lst as $key) {
            if (strpos($key, $fd . '/') === 0) {
                Cache::srem('u_chat' . $uid, $key);
            }
        }
    }

    public function chat_end(array $result)
    {
        app()->make(StoreServiceUserRepository::class)->online($result['frame']->uid, 0);
        $this->closeChat($result['frame']->uid, $result['frame']->fd);
    }

    public function service_chat_start(array $result)
    {
        $this->switchServiceChat($result['frame']->uid, $result['frame']->fd, $result['data']['mer_id'] ?? 0, $result['data']['uid']);
    }

    public function chat_start(array $result)
    {
        app()->make(StoreServiceUserRepository::class)->online($result['frame']->uid, 1);
        $this->switchChat($result['frame']->uid, $result['frame']->fd, $result['data']['mer_id'] ?? 0);
    }


    public function switchServiceChat($uid, $fd, $merId, $toUid)
    {
        $this->closeServiceChat($uid, $fd);
        Cache::sadd('m_chat' . $uid, $fd . '/' . $merId . '/' . $toUid);
    }

    public function switchChat($uid, $fd, $merId)
    {
        $this->closeChat($uid, $fd);
        Cache::sadd('u_chat' . $uid, $fd . '/' . $merId);
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
        $service = app()->make(StoreServiceRepository::class)->getService($frame->uid, (int)$data['mer_id']);
        if (!$service || !$service['status'])
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
        $log['send_date'] = date('H:i', strtotime($log['create_time']));
        SwooleTaskService::chatToUser([
            'uid' => $data['uid'],
            'data' => $log,
            'except' => [$frame->fd]
        ]);

        return app('json')->message('chat', $log);
    }

    /**
     * 用户给商户发
     * @param array $result
     * @return \think\response\Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function send_chat(array $result)
    {
        $data = $result['data'];
        $frame = $result['frame'];
        if (!isset($data['msn_type']) || !isset($data['msn']) || !isset($data['mer_id']))
            return app('json')->message('err_tip', '数据格式错误');
        if (!$data['msn']) return app('json')->message('err_tip', '请输入发送内容');
        if (!in_array($data['msn_type'], [1, 2, 3, 4, 5, 6, 7, 8, 100]))
            return app('json')->message('err_tip', '消息类型有误');
        if ($data['mer_id'] && !app()->make(MerchantRepository::class)->exists(intval($data['mer_id'])))
            return app('json')->message('err_tip', '商户不存在');
        if (!$data['mer_id'] && systemConfig('is_open_service') != 1)
            return app('json')->message('err_tip', '功能未开启');
        $service = app()->make(StoreServiceRepository::class)->getChatService($data['mer_id'], $frame->uid);
        if (!$service)
            return app('json')->message('err_tip', '该商户暂无有效客服');
        $data['msn'] = trim(strip_tags(htmlspecialchars_decode($data['msn'])));
        if (!$data['msn'])
            return app('json')->message('err_tip', '内容字符无效');
        $this->switchChat($frame->uid, $frame->fd, $data['mer_id']);
        $data['uid'] = $frame->uid;
        $data['service_id'] = $service->service_id;
        $data['send_type'] = 0;
        $storeServiceLogRepository = app()->make(StoreServiceLogRepository::class);
        try {
            $storeServiceLogRepository->checkMsn($data['mer_id'], $frame->uid, $data['msn_type'], $data['msn']);
        } catch (ValidateException $e) {
            return app('json')->message('err_tip', $e->getMessage());
        }
        if ($data['msn_type'] == 100) {
            if (!$storeServiceLogRepository->query(['service_log_id' => $data['msn']])
                ->where('create_time', '>', date('Y-m-d H:i:s', strtotime('- 120 seconds')))->where('uid', $data['uid'])->where('send_type', 0)->count()) {
                return app('json')->message('err_tip', '消息不能撤回');
            }
        }
        $log = $storeServiceLogRepository->create($data);
        if ($data['msn_type'] == 100) {
            $storeServiceLogRepository->query(['service_log_id' => $data['msn']])->delete();
        }
        app()->make(StoreServiceUserRepository::class)->updateInfo($log, true);
        $storeServiceLogRepository->getSendData($log);
        $log->user;
        $log = $log->toArray();
        $log['send_time'] = strtotime($log['create_time']);
        $log['send_date'] = date('H:i',strtotime($log['create_time']));
        //TODO 发送给客服,是否在线,发送提醒
        SwooleTaskService::chatToService([
            'uid' => $service->uid,
            'data' => $log,
            'except' => [$frame->fd]
        ]);

        return app('json')->message('chat', $log);
    }

    public function close($result)
    {
        if ($result['type'] === 'user') {
            app()->make(StoreServiceUserRepository::class)->online($result['uid'], 0);
            $this->closeChat($result['uid'], $result['fd']);
        } else {
            $this->closeServiceChat($result['uid'], $result['fd']);
        }
    }

}
