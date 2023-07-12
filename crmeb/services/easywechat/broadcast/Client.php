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


namespace crmeb\services\easywechat\broadcast;


use EasyWeChat\Core\Exceptions\HttpException;
use EasyWeChat\MiniProgram\Core\AbstractMiniProgram;

/**
 * Class Client.
 *
 * @author Abbotton <uctoo@foxmail.com>
 */
class Client extends AbstractMiniProgram
{
    const MSG_CODE = [
        '1' => '未创建直播间',
        '1003' => '商品id不存在',
        '47001' => '入参格式不符合规范',
        '200002' => '入参错误',
        '300001' => '禁止创建/更新商品 或 禁止编辑&更新房间',
        '300002' => '名称长度不符合规则',
        '300006' => '图片上传失败',
        '300022' => '此房间号不存在',
        '300023' => '房间状态 拦截',
        '300024' => '商品不存在',
        '300025' => '商品审核未通过',
        '300026' => '房间商品数量已经满额',
        '300027' => '导入商品失败',
        '300028' => '房间名称违规',
        '300029' => '主播昵称违规',
        '300030' => '主播微信号不合法',
        '300031' => '直播间封面图不合规',
        '300032' => '直播间分享图违规',
        '300033' => '添加商品超过直播间上限',
        '300034' => '主播微信昵称长度不符合要求',
        '300035' => '主播微信号不存在',
        '300003' => '价格输入不合规',
        '300004' => '商品名称存在违规违法内容',
        '300005' => '商品图片存在违规违法内容',
        '300007' => '线上小程序版本不存在该链接',
        '300008' => '添加商品失败',
        '300009' => '商品审核撤回失败',
        '300010' => '商品审核状态不对',
        '300011' => '操作非法',
        '300012' => '没有提审额度',
        '300013' => '提审失败',
        '300014' => '审核中，无法删除',
        '300017' => '商品未提审',
        '300018' => '图片尺寸不符合要求',
        '300021' => '商品添加成功，审核失败',
        '300036' => '请先在微信直播小程序中实名认证',
        '300038' => '请先在小程序后台配置直播客服',
        '-1' => '系统错误',
    ];

    const API = 'https://api.weixin.qq.com/';

    /**
     * @param $api
     * @param $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    protected function httpPostJson($api, $params)
    {
        try {
            return $this->parseJSON('json', [self::API . $api, $params]);
        } catch (HttpException $e) {
            $code = $e->getCode();
            throw new HttpException("接口异常[$code]" . (self::MSG_CODE[$code] ?? $e->getMessage()), $code);
        }
    }

    /**
     * @param $api
     * @param $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    protected function httpPost($api, $params)
    {
        try {
            return $this->parseJSON('post', [self::API . $api, $params]);
        } catch (HttpException $e) {
            $code = $e->getCode();
            throw new HttpException("接口异常[$code]" . (self::MSG_CODE[$code] ?? $e->getMessage()), $code);
        }
    }


    /**
     * @param $api
     * @param $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    protected function httpGet($api, $params)
    {
        try {
            return $this->parseJSON('get', [self::API . $api, $params]);
        } catch (HttpException $e) {
            $code = $e->getCode();
            throw new HttpException("接口异常[$code]" . (self::MSG_CODE[$code] ?? $e->getMessage()), $code);
        }
    }

    /**
     * Add broadcast goods.
     *
     * @param array $goodsInfo
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function create(array $goodsInfo)
    {
        $params = [
            'goodsInfo' => $goodsInfo,
        ];

        return $this->httpPostJson('wxaapi/broadcast/goods/add', $params);
    }

    /**
     * Reset audit.
     *
     * @param int $auditId
     * @param int $goodsId
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function resetAudit(int $auditId, int $goodsId)
    {
        $params = [
            'auditId' => $auditId,
            'goodsId' => $goodsId,
        ];

        return $this->httpPostJson('wxaapi/broadcast/goods/resetaudit', $params);
    }

    /**
     * Resubmit audit goods.
     *
     * @param int $goodsId
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function resubmitAudit(int $goodsId)
    {
        $params = [
            'goodsId' => $goodsId,
        ];

        return $this->httpPostJson('wxaapi/broadcast/goods/audit', $params);
    }

    /**
     * Delete broadcast goods.
     *
     * @param int $goodsId
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function delete(int $goodsId)
    {
        $params = [
            'goodsId' => $goodsId,
        ];
        try{
            return $this->httpPostJson('wxaapi/broadcast/goods/delete', $params);
        } catch (HttpException $exception) {
           if ($exception->getCode() ==  300015) return ;
        }
    }

    /**
     * Update goods info.
     *
     * @param array $goodsInfo
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function update(array $goodsInfo)
    {
        $params = [
            'goodsInfo' => $goodsInfo,
        ];

        return $this->httpPostJson('wxaapi/broadcast/goods/update', $params);
    }

    /**
     * Get goods information and review status.
     *
     * @param array $goodsIdArray
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function getGoodsWarehouse(array $goodsIdArray)
    {
        $params = [
            'goods_ids' => $goodsIdArray,
        ];

        return $this->httpPostJson('wxa/business/getgoodswarehouse', $params);
    }

    /**
     * Get goods list based on status
     *
     * @param array $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function getApproved(array $params)
    {
        return $this->httpGet('wxaapi/broadcast/goods/getapproved', $params);
    }

    /**
     * Add goods to the designated live room.
     *
     * @param array $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function addGoods(array $params)
    {
        return $this->httpPost('wxaapi/broadcast/room/addgoods', $params);
    }

    /**
     * Get Room List.
     *
     * @param int $start
     * @param int $limit
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function getRooms(int $start = 0, int $limit = 10)
    {
        $params = [
            'start' => $start,
            'limit' => $limit,
        ];

        return $this->httpPostJson('wxa/business/getliveinfo', $params);
    }

    /**
     * Get Playback List.
     *
     * @param int $roomId
     * @param int $start
     * @param int $limit
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function getPlaybacks(int $roomId, int $start = 0, int $limit = 10)
    {
        $params = [
            'action' => 'get_replay',
            'room_id' => $roomId,
            'start' => $start,
            'limit' => $limit,
        ];

        return $this->httpPostJson('wxa/business/getliveinfo', $params);
    }

    /**
     * Create a live room.
     *
     * @param array $params
     * @return \EasyWeChat\Support\Collection|null
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     */
    public function createLiveRoom(array $params)
    {
        return $this->httpPostJson('wxaapi/broadcast/room/create', $params);
    }

    /**
     * TODO
     * @param int $roomId
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/22/21
     */
    public function getPushUrl(int $roomId)
    {
        $params = [
            'roomId' => $roomId,
        ];
        return $this->httpGet('wxaapi/broadcast/room/getpushurl', $params);
    }

    /**
     * TODO 是否关闭客服 【0：开启，1：关闭】
     * @param int $roomId
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/22/21
     */
    public function closeKf(int $roomId,int $status)
    {
        $params = [
            'roomId' => $roomId,
            'closeKf' => $status ? 1 : 0,
        ];
        return $this->httpPostJson('wxaapi/broadcast/room/updatekf', $params);
    }

    /**
     * TODO 1-禁言，0-取消禁言
     * @param int $roomId
     * @param int $type
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/22/21
     */
    public function banComment(int $roomId, int $status)
    {
        $params = [
            'roomId' => $roomId,
            'banComment' => $status ? 1 : 0,
        ];
        return $this->httpPostJson('wxaapi/broadcast/room/updatecomment', $params);
    }

    /**
     * TODO 添加助手
     * @param array $params
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/25/21
     */
    public function addAssistant(array $params)
    {
        return $this->httpPostJson('wxaapi/broadcast/room/addassistant', $params);
    }

    /**
     * TODO 删除助手
     * @param array $params
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/25/21
     */
    public function removeAssistant(int $roomId, string $username)
    {
        $params = [
            'roomId' => $roomId,
            'username' => $username,
        ];
        return $this->httpPostJson('wxaapi/broadcast/room/removeassistant', $params);
    }

    /**
     * TODO 修改小助手
     * @param array $params
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/25/21
     */
    public function modifyAssistant(array $params)
    {
        return $this->httpPostJson('wxaapi/broadcast/room/modifyassistant', $params);
    }

    /**
     * TODO 助手列表
     * @param int $roomId
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/25/21
     * wxa/business/get_wxa_followers?access_token=


     */
    public function getAssistantList(int $roomId)
    {
        $params = [
            'roomId' => $roomId,
        ];
        return $this->httpGet('wxaapi/broadcast/room/getassistantlist', $params);
    }

    /**
     * TODO 获取长期订阅用户
     * @param int $roomId
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/25/21
     */
    public function getFollowers(string $page, int $limit = 2000)
    {
        $params['limit'] = $limit;
        if ($page) $params['page_break'] = $page;
        return $this->httpPostJson('wxa/business/get_wxa_followers', $params);
    }

    /**
     * TODO 群发发送订阅
     * @param int $roomId
     * @param array $data
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/25/21
     */
    public function pushMessage(int $roomId, array $data)
    {
        $params = [
            'room_id' => $roomId,
            'user_openid' => $data,
        ];

        return $this->httpPostJson('wxa/business/push_message', $params);
    }


    /**
     * TODO 更新官方收录
     * @param int $roomId
     * @param int $status
     * @return \EasyWeChat\Support\Collection|null
     * @author Qinii
     * @day 10/30/21
     */
    public function updateFeedPublic(int $roomId, int $status)
    {
        $params = [
            'roomId' => $roomId,
            'isFeedsPublic' => $status ? 1 : 0,
        ];

        return $this->httpPostJson('wxaapi/broadcast/room/updatefeedpublic', $params);
    }

    public function goodsOnsale(int $roomId, int $goodsId, int $status)
    {
        $params = [
            'roomId' => $roomId,
            'goodsId' => $goodsId,
            'onSale' => $status ? 1 : 0,
        ];

        return $this->httpPostJson('wxaapi/broadcast/goods/onsale', $params);
    }
}
