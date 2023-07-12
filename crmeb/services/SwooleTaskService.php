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


namespace crmeb\services;


use Swoole\Server;
use think\facade\Log;

/**
 * Class SwooleTaskService
 * @package crmeb\services
 * @author xaboy
 * @day 2020-04-15
 */
class SwooleTaskService
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var
     */
    protected $callback;

    /**
     * SwooleTaskService constructor.
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
        $this->server = app('swoole.server');
    }

    /**
     * @param array $data
     * @return $this
     * @author xaboy
     * @day 2020-04-15
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param int $workId
     * @return mixed
     * @author xaboy
     * @day 2020-04-15
     */
    public function push(int $workId = -1)
    {
        try {

            return $this->server->task(['type' => $this->type, 'data' => $this->data], $workId);
        } catch (\Exception $e) {
            Log::info('发送 Task 失败' . $e->getMessage());
        }
    }

    /**
     * @param string $type
     * @return static
     * @author xaboy
     * @day 2020/5/25
     */
    public static function create(string $type)
    {
        return new static($type);
    }

    public static function user($uid, $data)
    {
        $type = 'user';
        return self::create('message')->setData(compact('type', 'uid', 'data'))->push();
    }

    public static function admin(string $msgType, array $msgData, $uid = [])
    {
        return self::create('admin')->setData([
            'uid' => $uid,
            'data' => [
                'type' => $msgType,
                'data' => $msgData
            ]
        ])->push();
    }

    public static function merchant(string $msgType, array $msgData, int $merId, $uid = [])
    {
        return self::create('merchant')->setData([
            'uid' => $uid,
            'mer_id' => $merId,
            'data' => [
                'type' => $msgType,
                'data' => $msgData
            ]
        ])->push();
    }


    /**
     * @param $merId
     * @param array $result
     * @return mixed
     * @author xaboy
     * @day 2020/5/25
     */
    public static function log($merId, array $result)
    {
        return self::create('log')->setData(compact('merId', 'result'))->push();
    }

    /**
     * @param int $uid
     * @param int $type_id
     * @param string $type
     * @param string $content
     * @return mixed
     * @author xaboy
     * @day 2020/6/25
     */
    public static function visit(int $uid, int $type_id, string $type, string $content = '')
    {
        return self::create('visit')->setData(compact('uid', 'type', 'type_id', 'content'))->push();
    }


    public static function __callStatic($name, $arguments)
    {
        return self::create($name)->setData($arguments[0])->push();
    }

}
