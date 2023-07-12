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

use app\common\repositories\store\service\StoreServiceLogRepository;
use app\common\repositories\store\service\StoreServiceReplyRepository;
use app\common\repositories\store\service\StoreServiceUserRepository;
use app\common\repositories\system\admin\AdminLogRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\user\UserVisitRepository;
use app\webscoket\handler\UserHandler;
use app\webscoket\Manager;
use crmeb\interfaces\ListenerInterface;
use crmeb\jobs\SendSmsJob;
use Swoole\Server;
use Swoole\Server\Task;
use think\facade\Cache;
use think\facade\Queue;

class SwooleTaskListen implements ListenerInterface
{
    /**
     * @var Task
     */
    protected $task;

    public function handle($task): void
    {
        request()->clearCache();
        $this->task = $task;
        if (method_exists($this, $task->data['type']))
            $this->{$task->data['type']}($task->data['data']);
    }

    public function message(array $data)
    {
        $server = app()->make(Server::class);
        $uid = is_array($data['uid']) ? $data['uid'] : [$data['uid']];
        $except = $data['except'] ?? [];
        if (!count($uid) && $data['type'] != 'user') {
            $fds = $data['type'] == 'mer' ? Manager::merFd($data['mer_id'] ?? 0) : Manager::userFd(0);
            foreach ($fds as $fd) {
                if (!in_array($fd, $except) && $server->isEstablished($fd) && $server->exist($fd))
                    $server->push((int)$fd, json_encode($data['data']));
            }
        } else {
            foreach ($uid as $id) {
                $fds = Manager::userFd(array_search($data['type'], Manager::USER_TYPE), $id);
                foreach ($fds as $fd) {
                    if (!in_array($fd, $except) && $server->isEstablished($fd) && $server->exist($fd))
                        $server->push((int)$fd, json_encode($data['data']));
                }
            }
        }
    }

    /**
     * //TODO 用户给客服发送消息
     *
     * @param array $data
     * @author xaboy
     * @day 2020/6/15
     */
    public function chatToService(array $data)
    {
        $flag = UserHandler::serviceOnline($data['uid'], $data['data']['uid']);
        $serviceLogRepository = app()->make(StoreServiceLogRepository::class);
        $lst = Cache::sMembers('m_chat' . $data['uid']) ?: [];
        $server = app()->make(Server::class);
        foreach ($lst as $item) {
            [$fd, $merId, $toUid] = explode('/', $item);
            if (!in_array($fd, $data['except'] ?? []) && $server->isEstablished($fd) && $server->exist($fd)) {
                $data['data']['is_get'] = 1;
                $server->push((int)$fd, json_encode(['type' => $toUid == $data['data']['uid'] ? 'chat' : 'back_chat', 'data' => $data['data']]));
            }
        }
        if (!$flag) {
            //TODO 客服消息提醒
            $user = app()->make(UserRepository::class)->get($data['data']['uid']);
            $params = [
                'mer_id' => $data['data']['mer_id'],
                'keyword1' => date('Y-m-d H:i:s',time()),
                'keyword2' => $data['data']['msn'],
                'url' => '/pages/chat/customer_list/chat?userId=' . $data['data']['uid'] . '&mer_id=' . $data['data']['mer_id']
            ];
            Queue::push(SendSmsJob::class, ['tempId' => 'SERVER_NOTICE', 'id' => $data['uid'], 'params' => $params]);
        }else{
            $serviceLogRepository->serviceRead($data['data']['mer_id'], $data['data']['uid'], $data['data']['service_id']);
            app()->make(StoreServiceUserRepository::class)->read($data['data']['mer_id'], $data['data']['uid'], true);
        }
        if ($data['data']['msn_type'] === 1) {
            $serviceLogRepository = app()->make(StoreServiceLogRepository::class);
            $reply = app()->make(StoreServiceReplyRepository::class)->keywordByValidData($data['data']['msn'], $data['data']['mer_id']);
            if ($reply) {
                $log = null;
                if (($reply->type === 2 || $reply->type === 1) && $reply['content']) {
                    $log = $serviceLogRepository->create([
                        'mer_id' => $data['data']['mer_id'],
                        'msn' => $reply['content'],
                        'uid' => $data['data']['uid'],
                        'service_id' => $data['data']['service_id'],
                        'remind' => 1,
                        'send_type' => 1,
                        'msn_type' => $reply->type === 2 ? 3 : 1,
                        'type' => 1,
                        'service_type' => 0,
                    ]);
                }
                if ($log) {
                    $lst = Cache::sMembers('u_chat' . $data['data']['uid']) ?: [];
                    $log->append(['service']);
                    $server = app()->make(Server::class);
                    foreach ($lst as $item) {
                        [$fd, $merId] = explode('/', $item);
                        if ($server->isEstablished($fd) && $server->exist($fd) && $merId == $data['data']['mer_id']) {
                            $server->push((int)$fd, json_encode(['type' => 'chat', 'data' => $log->toArray()]));
                        }
                    }
                    $lst = Cache::sMembers('m_chat' . $log->service->uid) ?: [];
                    $server = app()->make(Server::class);
                    foreach ($lst as $item) {
                        [$fd, $merId, $toUid] = explode('/', $item);
                        if ($server->isEstablished($fd) && $server->exist($fd) && $toUid == $data['data']['uid']) {
                            $server->push((int)$fd, json_encode(['type' => 'chat', 'data' => $log->toArray()]));
                        }
                    }
                }
            }
        }
    }

    /**
     * //TODO 客服给用户发送消息
     * @param array $data
     * @author xaboy
     * @day 2020/6/15
     */
    public function chatToUser(array $data)
    {
        $flag = UserHandler::userOnline($data['uid'], $data['data']['mer_id']);
        if ($flag) {
            $serviceLogRepository = app()->make(StoreServiceLogRepository::class);
            $lst = Cache::sMembers('u_chat' . $data['uid']) ?: [];
            $server = app()->make(Server::class);
            foreach ($lst as $item) {
                [$fd, $merId] = explode('/', $item);
                if (!in_array($fd, $data['except'] ?? []) && $server->isEstablished($fd) && $server->exist($fd) && $merId == $data['data']['mer_id']) {
                    $data['data']['is_get'] = 1;
                    $server->push((int)$fd, json_encode(['type' => 'chat', 'data' => $data['data']]));
                }
            }
            $serviceLogRepository->userRead($data['data']['mer_id'], $data['data']['uid']);
            app()->make(StoreServiceUserRepository::class)->read($data['data']['mer_id'], $data['data']['uid']);
        } else {
            //TODO 客服给用户发送消息
            $params = [
                'mer_id' => $data['data']['mer_id'],
                'keyword1' =>  date('Y-m-d H:i:s', time()),
                'keyword2' =>  $data['data']['msn'],
                'url' =>   '/pages/chat/customer_list/chat?mer_id=' . $data['data']['mer_id']
            ];
            Queue::push(SendSmsJob::class, ['id' => $data['uid'], 'tempId' => 'SERVER_NOTICE', 'params' => $params]);
        }
    }

    public function admin(array $data)
    {
        $this->message([
                'uid' => $data['uid'] ?? [],
                'type' => 'admin',
                'data' => $data['data']
            ]
        );
    }

    public function merchant(array $data)
    {
        $this->message([
                'uid' => $data['uid'] ?? [],
                'mer_id' => $data['mer_id'],
                'type' => 'mer',
                'data' => $data['data']
            ]
        );
    }

    public function visit(array $data)
    {
        /** @var UserVisitRepository $make */
        $make = app()->make(UserVisitRepository::class);
        $make->create($data);
    }

    public function log(array $data)
    {
        app()->make(AdminLogRepository::class)->create($data['merId'], $data['result']);
    }
}
