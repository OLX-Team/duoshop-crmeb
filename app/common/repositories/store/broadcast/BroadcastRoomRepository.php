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


namespace app\common\repositories\store\broadcast;


use app\common\dao\store\broadcast\BroadcastRoomDao;
use app\common\model\store\broadcast\BroadcastRoom;
use app\common\repositories\BaseRepository;
use crmeb\jobs\SendSmsJob;
use crmeb\services\DownloadImageService;
use crmeb\services\MiniProgramService;
use crmeb\services\SwooleTaskService;
use EasyWeChat\Core\Exceptions\HttpException;
use Exception;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Route;

/**
 * Class BroadcastRoomRepository
 * @package app\common\repositories\store\broadcast
 * @author xaboy
 * @day 2020/7/29
 * @mixin BroadcastRoomDao
 */
class BroadcastRoomRepository extends BaseRepository
{
    /**
     * @var BroadcastRoomDao
     */
    protected $dao;

    /**
     * BroadcastRoomRepository constructor.
     * @param BroadcastRoomDao $dao
     */
    public function __construct(BroadcastRoomDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList($merId, array $where, $page, $limit)
    {
        $where['mer_id'] = $merId;
        $query = $this->dao->search($where)->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    public function userList(array $where, $page, $limit)
    {
        $where['show_tag'] = 1;
        $query = $this->dao->search($where)->with([
            'broadcast' => function($query) {
                $query->where('on_sale',1);
                $query->with('goods');
            }
        ])->where('room_id', '>', 0)
            ->whereNotIn('live_status', [107])->order('star DESC, sort DESC, create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        foreach ($list as $item) {
            $item->show_time = date('m/d H:i', strtotime($item->start_time));
        }
        return compact('count', 'list');
    }

    public function adminList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where)->with(['merchant' => function ($query) {
            $query->field('mer_name,mer_id,is_trader');
        }])->order('BroadcastRoom.star DESC, BroadcastRoom.sort DESC, BroadcastRoom.create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020/7/29
     */
    public function createForm()
    {
        return Elm::createForm(Route::buildUrl('merchantBroadcastRoomCreate')->build(), [
            Elm::input('name', '直播间名字')->required(),
            Elm::frameImage('cover_img', '背景图', '/' . config('admin.merchant_prefix') . '/setting/uploadPicture?field=cover_img&type=1')
                ->info('建议像素1080*1920，大小不超过2M')->modal(['modal' => false])->width('896px')->height('480px')->props(['footer' => false])->required(),

            Elm::frameImage('share_img', '分享图', '/' . config('admin.merchant_prefix') . '/setting/uploadPicture?field=share_img&type=1')
                ->info('建议像素800*640，大小不超过1M')->modal(['modal' => false])->width('896px')->height('480px')->props(['footer' => false])->required(),

            Elm::frameImage('feeds_img', '封面图', '/' . config('admin.merchant_prefix') . '/setting/uploadPicture?field=feeds_img&type=1')
                ->info('建议像素800*800，大小不超过1M')->modal(['modal' => false])->width('896px')->height('480px')->props(['footer' => false])->required(),

            Elm::input('anchor_name', '主播昵称')->required()->placeholder('请输入主播昵称，主播需通过小程序直播认证，否则会提交失败。'),
            Elm::input('anchor_wechat', '主播微信号')->required()->placeholder('请输入主播微信号，主播需通过小程序直播认证，否则会提交失败。'),
            Elm::input('phone', '联系电话')->required(),
            Elm::dateTimeRange('start_time', '直播时间')->value([])->required(),
            Elm::radio('type', '直播间类型', 0)->options([['value' => 0, 'label' => '手机直播'],['value' => 1, 'label' => '推流']]),
            Elm::radio('screen_type', '显示样式', 0)->options([['value' => 0, 'label' => '竖屏'], ['value' => 1, 'label' => '横屏']]),

            Elm::switches('close_like', '是否开启点赞', 0)
                ->activeValue(0)->inactiveValue(1)
                ->activeText('开')->inactiveText('关'),

            Elm::switches('close_goods', '是否开启货架', 0)
                ->activeValue(0)->inactiveValue(1)
                ->activeText('开')->inactiveText('关'),

            Elm::switches('close_comment', '是否开启评论', 0)
                ->activeValue(0)->inactiveValue(1)
                ->activeText('开')->inactiveText('关'),

            Elm::switches('replay_status', '是否开启回放', 0)
                ->activeValue(1)->inactiveValue(0)
                ->activeText('开')->inactiveText('关'),

            Elm::switches('close_share', '是否开启分享', 0)
                ->activeValue(0)->inactiveValue(1)
                ->activeText('开')->inactiveText('关'),

            Elm::switches('close_kf', '是否开启客服', 0)
                ->activeValue(0)->inactiveValue(1)
                ->activeText('开')->inactiveText('关'),

            Elm::switches('is_feeds_public', '是否开启官方收录', 1)
                ->activeValue(1)->inactiveValue(0)
                ->activeText('开')->inactiveText('关'),

        ])->setTitle('创建直播间');
    }

    public function updateForm($id)
    {
        $data = $this->dao->get($id)->toArray();
        $data['start_time'] = [$data['start_time'], $data['end_time']];
        return $this->createForm()->setAction(Route::buildUrl('merchantBroadcastRoomUpdate', compact('id'))->build())->formData($data)->setTitle('编辑直播间');
    }

    public function create($merId, array $data)
    {
        $data['status'] = request()->merchant()->is_bro_room == 1 ? 0 : 1;
        $data['mer_id'] = $merId;
        return Db::transaction(function () use ($data) {
            $room = $this->dao->create($data);
            if ($data['status'] == 1) {
                $room->room_id = $this->wxCreate($room);
                $room->status = 2;
                $room->save();
            } else {
                SwooleTaskService::admin('notice', [
                    'type' => 'new_broadcast',
                    'data' => [
                        'title' => '新直播间申请',
                        'message' => '您有1个新的直播间审核，请及时处理！',
                        'id' => $room->broadcast_room_id
                    ]
                ]);
            }
            return $room;
        });
    }

    public function updateRoom($merId, $id, array $data)
    {
        $data['status'] = 0;
        $room = $this->dao->getWhere(['mer_id' => $merId, 'broadcast_room_id' => $id]);
        $room->save($data);
        SwooleTaskService::admin('notice', [
            'type' => 'new_broadcast',
            'data' => [
                'title' => '新直播间申请',
                'message' => '您有1个新的直播间审核，请及时处理！',
                'id' => $room->broadcast_room_id
            ]
        ]);
    }

    public function applyForm($id)
    {
        return Elm::createForm(Route::buildUrl('systemBroadcastRoomApply', compact('id'))->build(), [
            Elm::radio('status', '审核状态', '1')->options([['value' => '-1', 'label' => '未通过'], ['value' => '1', 'label' => '通过']])->control([
                ['value' => '-1', 'rule' => [
                    Elm::textarea('msg', '未通过原因', '信息有误,请完善')->required()
                ]]
            ]),
        ])->setTitle('审核直播间');
    }

    public function apply($id, $status, $msg = '')
    {
        $room = $this->dao->get($id);
        Db::transaction(function () use ($msg, $status, $room) {
            $room->status = $status;
            if ($status == -1)
                $room->error_msg = $msg;
            else {
                $room_id = $this->wxCreate($room);
                $room->room_id = $room_id;
                $room->status = 2;
                if ($room->type) {
                    $path =  MiniProgramService::create()->miniBroadcast()->getPushUrl($room_id);
                    $room->push_url = $path->pushAddr;
                }
            }
            $room->save();

            SwooleTaskService::merchant('notice', [
                'type' => 'broadcast_status_' . ($status == -1 ? 'fail' : 'success'),
                'data' => [
                    'title' => '直播间审核通知',
                    'message' => $status == -1 ? '您的直播间审核未通过!' : '您的直播间审核已通过',
                    'id' => $room->broadcast_room_id
                ]
            ], $room->mer_id);

            if ($status == -1) {
                Queue::push(SendSmsJob::class, [
                    'tempId' => 'BROADCAST_ROOM_FAIL',
                    'id' => $room['broadcast_room_id']
                ]);
            }
        });
    }

    public function wxCreate(BroadcastRoom $room)
    {
        if ($room['room_id'])
            throw new ValidateException('直播间已创建');

        $room = $room->toArray();
        $miniProgramService = MiniProgramService::create();
        $DownloadImageService = app()->make(DownloadImageService::class);
        $coverImg = './public' . $DownloadImageService->downloadImage($room['cover_img'],'def','',1)['path'];
        $shareImg = './public' . $DownloadImageService->downloadImage($room['share_img'],'def','',1)['path'];
        $feedsImg = './public' . $DownloadImageService->downloadImage($room['feeds_img'],'def','',1)['path'];
        $data = [
            //是
            'name' => $room['name'],
            'coverImg' => $miniProgramService->material()->uploadImage($coverImg)->media_id,
            'startTime' => strtotime($room['start_time']),
            'endTime' => strtotime($room['end_time']),
            'anchorName' => $room['anchor_name'],
            'anchorWechat' => $room['anchor_wechat'],
            'shareImg' => $miniProgramService->material()->uploadImage($shareImg)->media_id,
            'feedsImg' => $miniProgramService->material()->uploadImage($feedsImg)->media_id,
            'type'  => $room['type'],
            'closeLike' => $room['close_like'],
            'closeGoods' => $room['close_goods'],
            'closeComment' => $room['close_comment'],

            //否
            'screenType' => $room['screen_type'],
            'closeShare' => $room['close_share'],
            'closeKf' => $room['close_kf'],
            'closeReplay' => $room['replay_status'] == 1 ? 0 : 1,
            'isFeedsPublic' => $room['is_feeds_public'] == 1 ? 0 : 1,
        ];
        @unlink($coverImg);
        @unlink($shareImg);
        @unlink($feedsImg);

        try {
            $roomId = $miniProgramService->miniBroadcast()->createLiveRoom($data)->roomId;
        } catch (Exception $e) {
            throw new ValidateException($e->getMessage());
        }
        Queue::push(SendSmsJob::class, [
            'tempId' => 'BROADCAST_ROOM_CODE',
            'id' => $room['broadcast_room_id']
        ]);
        return $roomId;
    }

    public function isShow($id, $isShow, bool $admin = false)
    {
        return $this->dao->update($id, [($admin ? 'is_show' : 'is_mer_show') => $isShow]);
    }

    public function mark($id, $mark)
    {
        return $this->dao->update($id, compact('mark'));
    }

    /**
     * @param $merId
     * @param array $ids
     * @param $roomId
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/7/31
     */
    public function exportGoods($merId, array $ids, $roomId)
    {
        $broadcastGoodsRepository = app()->make(BroadcastGoodsRepository::class);
        if (count($ids) != count($goods = $broadcastGoodsRepository->goodsList($merId, $ids)))
            throw new ValidateException('请选择正确的直播商品');
        if (!$room = $this->dao->validRoom($roomId, $merId))
            throw new ValidateException('直播间状态有误');
        $broadcastRoomGoodsRepository = app()->make(BroadcastRoomGoodsRepository::class);
        $goodsId = $broadcastRoomGoodsRepository->goodsId($room->broadcast_room_id);
        $ids = [];
        $data = [];
        foreach ($goods as $item) {
            if (!in_array($item->broadcast_goods_id, $goodsId)) {
                $data[] = [
                    'broadcast_room_id' => $room->broadcast_room_id,
                    'broadcast_goods_id' => $item->broadcast_goods_id
                ];
                $ids[] = $item->goods_id;
            }
        }
        if (!count($ids)) return;
        Db::transaction(function () use ($ids, $broadcastRoomGoodsRepository, $goods, $room, $data) {
            $broadcastRoomGoodsRepository->insertAll($data);
            MiniProgramService::create()->miniBroadcast()->addGoods(['roomId' => $room->room_id, 'ids' => $ids]);
        });
    }

    public function rmExportGoods($merId, $roomId, $id)
    {
        if (!$this->dao->merExists($roomId, $merId))
            throw new ValidateException('直播间不存在');
        app()->make(BroadcastRoomGoodsRepository::class)->rmGoods($id, $roomId);
    }

    /**
     * @throws HttpException
     * @throws DbException
     * @author xaboy
     * @day 2020/7/31
     */
    public function syncRoomStatus()
    {
        $start = 0;
        $limit = 50;
        $client = MiniProgramService::create()->miniBroadcast();
        do {
            $data = $client->getRooms($start, $limit)->room_info;
            $start += 50;
            $rooms = $this->getRooms(array_column($data, 'roomid'));
            foreach ($data as $room) {
                if (isset($rooms[$room['roomid']]) && $room['live_status'] != $rooms[$room['roomid']]['live_status']) {
                    $this->dao->update($rooms[$room['roomid']]['broadcast_room_id'], ['live_status' => $room['live_status']]);
                }
            }
        } while (count($data) >= $limit);
    }

    public function merDelete($id)
    {
//        $room = $this->dao->get($id);
//        if ($room && ($room->status == -1 || $room->status == 0 || $room->live_status == 107 || $room->live_status == 103)) {
        return $this->dao->merDelete($id);
//        }
//        throw new ValidateException('状态有误,删除失败');
    }

    public function closeInfo($id, string $type, int $status, $check = true, $data = [])
    {
        $room = $this->dao->get($id);
        if ($room->status !== 2) throw new ValidateException('直播间还未审核通过，无法修改');
        if (!$room) throw new ValidateException('数据不存在');

        if ($check && $room[$type] == -1) {
            throw new ValidateException('平台已关闭，您无法修改');
        }

        Db::transaction(function () use ($room, $id, $type, $status,$data) {
            $client  = MiniProgramService::create()->miniBroadcast();
            switch ($type) {
                case 'close_kf':
                    $client->closeKf($room->room_id, $status);
                    $room->close_kf = $status;
                    break;
                case 'close_comment':
                    $client->banComment($room->room_id, $status);
                    $room->close_comment = $status;
                    break;
                case 'is_feeds_public':
                    $client->updateFeedPublic($room->room_id,$status);
                    $room->is_feeds_public = $status;
                    break;
                case 'on_sale':
                    $ret = app()->make(BroadcastRoomGoodsRepository::class)->getWhere([
                        'broadcast_room_id' => $id,
                        'broadcast_goods_id' => $data['goods_id'],
                    ],'*',['goods']);
                    if (!isset($ret['goods']['goods_id'])) throw new ValidateException('数据不存在');
                    $ret->on_sale = $status;
                    $ret->save();
                    $client->goodsOnsale($room->room_id,$ret['goods']['goods_id'],$status);
                    break;
            }
            $room->save();
        });
    }

    public function assistantForm(int $id, int $merId)
    {
        $make = app()->make(BroadcastAssistantRepository::class);
        $get  = $this->dao->get($id);
        if ($get->status !== 2) throw new ValidateException('直播间还未审核通过，无法操作');
        $data = $make->options($merId);
        $has  = $make->intersection($get->assistant_id, $merId);
        return Elm::createForm(Route::buildUrl('merchantBroadcastAddAssistant', compact('id'))->build(),
            [
            Elm::selectMultiple('assistant_id', '小助手')->options(function () use ($data) {
                $options = [];
                if ($data) {
                    foreach ($data as $value => $label) {
                        $options[] = compact('value', 'label');
                    }
                }
                return $options;
            })
        ])->setTitle('修改小助手');
    }

    public function editAssistant(int $id, int $merId, array $data)
    {
        $make = app()->make(BroadcastAssistantRepository::class);
        $make->existsAll($data, $merId);
        Db::transaction(function() use($id, $data){
            $get = $this->dao->get($id);
            $old = explode(',', $get->assistant_id);
            $remove = array_diff($old, $data);
            $add = array_diff($data, $old);

            $this->addAssistant($get->room_id, $add);
            $this->removeAssistant($get->room_id, $remove);
            $get->assistant_id = implode(',', $data);
            $get->save();
        });

    }

    public function removeAssistant($roomId, array $ids)
    {
        $make = app()->make(BroadcastAssistantRepository::class);
        $data = $make->getSearch(['assistant_ids' => $ids])->select();

        foreach ($data as $datum) {
            MiniProgramService::create()->miniBroadcast()->removeAssistant($roomId,$datum->username);
        }
    }

    public function addAssistant($roomId, array $ids)
    {
        $make = app()->make(BroadcastAssistantRepository::class);
        $data = $make->getSearch(['assistant_ids' => $ids])->column('username,nickname');
        $params = [
            'roomId' => $roomId,
            'users'  => $data
        ];
        MiniProgramService::create()->miniBroadcast()->addAssistant($params);
    }

    public function pushMessage(int $id)
    {
        $get = $this->dao->get($id);
        $make  = MiniProgramService::create()->miniBroadcast();
        $page_break = '';

        do{
            $data  = $make->getFollowers($page_break);
            $restult = [];
            if ($data['errcode']  !== 0) throw new ValidateException($data['errmsg']);
            foreach ($data['followers'] as $datum) {
                if ($datum['room_id']  == $get->room_id) {
                    $restult[] = $datum['openid'];
                }
            }
            if ($restult) {
                $make->pushMessage($get->room_id, $restult);
            }
            $page_break = $data['page_break'] ?? '';
        }while($page_break);
    }
}
