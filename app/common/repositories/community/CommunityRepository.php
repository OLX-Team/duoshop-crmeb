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

namespace app\common\repositories\community;

use app\common\dao\community\CommunityDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\system\RelevanceRepository;
use app\common\repositories\user\UserBrokerageRepository;
use app\common\repositories\user\UserRepository;
use crmeb\services\QrcodeService;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;

class CommunityRepository extends BaseRepository
{
    /**
     * @var CommunityDao
     */
    protected $dao;

    const IS_SHOW_WHERE = [
        'is_show' => 1,
        'status'  => 1,
        'is_del'  => 0,
    ];

    public const COMMUNIT_TYPE_FONT = '1';
    public const COMMUNIT_TYPE_VIDEO = '2';

    /**
     * CommunityRepository constructor.
     * @param CommunityDao $dao
     */
    public function __construct(CommunityDao $dao)
    {
        $this->dao = $dao;
    }

    public function title(array $where)
    {
        $where['is_type'] = self::COMMUNIT_TYPE_FONT;
        $list[] = [
            'count' => $this->dao->search($where)->count(),
            'title' => '图文列表',
            'type' => self::COMMUNIT_TYPE_FONT,
        ];
        $where['is_type'] = self::COMMUNIT_TYPE_VIDEO;
        $list[] = [
            'count' => $this->dao->search($where)->count(),
            'title' => '短视频列表',
            'type' => self::COMMUNIT_TYPE_VIDEO,
        ];
        return  $list;
    }

    public function getList(array $where, int $page, int $limit)
    {
        $query = $this->dao->search($where)->with([
            'author' => function($query) {
                $query->field('uid,real_name,status,avatar,nickname,count_start');
            },
            'topic' => function($query) {
                $query->where('status', 1)->where('is_del',0);
                $query->field('topic_id,topic_name,status,category_id,pic,is_del');
            },
            'category'
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }


    public function getApiList(array $where, int $page, int $limit, $userInfo)
    {
        $config = systemConfig("community_app_switch");
        if (!isset($where['is_type']) && $config) $where['is_type'] = $config;
        $where['is_del'] = 0;
        $query = $this->dao->search($where)->order('start DESC,Community.create_time DESC,community_id DESC');
        $query->with([
            'author' => function($query) use($userInfo){
                $query->field('uid,real_name,status,avatar,nickname,count_start');
            },
            'is_start' => function($query) use ($userInfo) {
                $query->where('left_id',$userInfo->uid ?? null);
            },
            'topic' => function($query) {
                $query->where('status', 1)->where('is_del',0);
                $query->field('topic_id,topic_name,status,category_id,pic,is_del');
            },
            'relevance'  => [
                'spu' => function($query) {
                    $query->field('spu_id,store_name,image,price,product_type,activity_id,product_id');
                }
            ],
            'is_fans' => function($query) use($userInfo){
                $query->where('left_id',$userInfo->uid??  0);
            }
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->setOption('field',[])
            ->field('community_id,title,image,topic_id,Community.count_start,count_reply,start,Community.create_time,Community.uid,Community.status,is_show,content,video_link,is_type,refusal')
            ->select()->append(['time']);
        if ($list) $list = $list->toArray();
        return compact('count','list');
    }

    public function getFirtVideo($where,$page, $userInfo)
    {
        $with =[];
        if ($page == 1) {
            $with = [
                'author' => function($query) {
                    $query->field('uid,real_name,status,avatar,nickname,count_start');
                },
                'is_start' => function($query) use ($userInfo) {
                    $query->where('left_id',$userInfo->uid ?? null);
                },
                'topic' => function($query) {
                    $query->where('status', 1)->where('is_del',0);
                    $query->field('topic_id,topic_name,status,category_id,pic,is_del');
                },
                'relevance'  => [
                    'spu' => function($query) {
                        $query->field('spu_id,store_name,image,price,product_type,activity_id,product_id,status');
                    }
                ],
                'is_fans' => function($query) use($userInfo){
                    $query->where('left_id',$userInfo->uid??  0);
                }
            ];
        }
        return $this->dao->getSearch($where)->with($with)->field('community_id,image,title,topic_id,count_start,count_reply,start,create_time,uid,status,is_show,content,video_link,is_type,refusal')->find();
    }

    public function getApiVideoList(array $where, int $page, int $limit, $userInfo, $type = 0)
    {
        $where['is_type'] = self::COMMUNIT_TYPE_VIDEO;
        $first = $this->getFirtVideo($where,$page, $userInfo);
        if ($type) { // 点赞过的内容
            $where['uid'] = $userInfo->uid;
            $where['community_ids'] = $this->dao->joinUser($where)->column('community_id');
        } else { // 条件视频
            if (!isset($where['uid']) && $first) $where['topic_id'] = $first['topic_id'];
        }
        $where['not_id'] = $where['community_id'];
        unset($where['community_id']);
        $data = $this->getApiList($where, $page, $limit, $userInfo);
        if (empty($data['list']) && isset($where['topic_id'])) {
            unset($where['topic_id']);
            $data = $this->getApiList($where, $page, $limit, $userInfo);
        }
        if ($page == 1 && $first) {
            array_unshift($data['list'],$first->toArray());
        }
        return $data;
    }

    /**
     * TODO 后台详情
     * @param int $id
     * @return array|\think\Model|null
     * @author Qinii
     * @day 10/28/21
     */
    public function detail(int $id)
    {
        $where = [
            $this->dao->getPk() => $id,
            'is_del' => 0
        ];
        $config = systemConfig("community_app_switch");
        if ($config) $where['is_type'] = $config;
        return $this->dao->getSearch($where)->with([
            'author' => function($query) {
                $query->field('uid,real_name,status,avatar,nickname,count_start');
            },
            'topic',
            'category',
            'relevance.spu'
        ])->find();
    }

    /**
     * TODO 移动端详情展示
     * @param int $id
     * @param $user
     * @return array|\think\Model|null
     * @author Qinii
     * @day 10/27/21
     */

    public function show(int $id, $user)
    {
        $where = self::IS_SHOW_WHERE;
        $is_author = 0;
        if ($user && $this->dao->uidExists($id, $user->uid)) {
            $where = ['is_del' => 0];
            $is_author = 1;
        }
        $config = systemConfig("community_app_switch");
        if ($config) $where['is_type'] = $config;
        $where[$this->dao->getPk()] = $id;
        $data = $this->dao->getSearch($where)
            ->with([
                'author' => function ($query) {
                    $query->field('uid,real_name,status,avatar,nickname,count_start,member_level');
                    if (systemConfig('member_status')) $query->with(['member' => function ($query) {
                        $query->field('brokerage_icon,brokerage_level');
                    }]);
                },
                'relevance' => [
                    'spu' => function ($query) {
                        $query->field('spu_id,store_name,image,price,product_type,activity_id,product_id');
                    }
                ],
                'topic' => function ($query) {
                    $query->where('status', 1)->where('is_del', 0);
                    $query->field('topic_id,topic_name,status,category_id,pic,is_del');
                },
                'is_start' => function ($query) use ($user) {
                    $query->where('left_id', $user->uid ?? '');
                },
            ])->hidden(['is_del'])->find();

        if (!$data) throw new ValidateException('内容不存在，可能已被删除了哦～');

        $data['is_author'] = $is_author;
        $is_fans = 0;
        if ($user && !$data['is_author'])
            $is_fans = app()->make(RelevanceRepository::class)->getWhereCount([
            'left_id' => $user->uid,
            'right_id' => $data['uid'],
            'type' => RelevanceRepository::TYPE_COMMUNITY_FANS,
        ]);
        $data['is_fans'] = $is_fans;
        return $data;
    }

    public function getSpuByOrder($id, $uid)
    {
        $where = app()->make(StoreOrderProductRepository::class)->selectWhere(['order_id' => $id]);
        if (!$where) throw new  ValidateException('商品已下架');

        $make = app()->make(SpuRepository::class);
        foreach ($where as $item) {
            switch ($item['product_type']){
                case 0:
                    $sid = $item['product_id'];
                   // nobreak;
                case 1:
                    $sid = $item['product_id'];
                    break;
                case 2:
                    $sid = $item['activity_id'];
                    break;
                case 3:
                    $sid = $item['cart_info']['productAssistSet']['product_assist_id'];
                    break;
                case 4:
                    $sid = $item['cart_info']['product']['productGroup']['product_group_id'];
                    break;
                default:
                    $sid = $item['product_id'];
                    break;

            }
            $data[] = $make->getSpuData($sid, $item['product_type'],0);
        }
        return $data;
    }

    /**
     * TODO 创建
     * @param array $data
     * @author Qinii
     * @day 10/29/21
     */
    public function create(array $data)
    {
        event('community.create.before',compact('data'));
        if ($data['topic_id']) {
            $getTopic  =  app()->make(CommunityTopicRepository::class)->get($data['topic_id']);
            if (!$getTopic || !$getTopic->status) throw new ValidateException('话题不存在或已关闭');
            $data['category_id'] = $getTopic->category_id;
        }
        return Db::transaction(function () use($data) {
            $community = $this->dao->create($data);
            if ($data['spu_id'])$this->joinProduct($community->community_id,$data['spu_id']);
            event('community.create',compact('community'));
            return $community->community_id;
        });
    }

    /**
     * TODO 编辑
     * @param int $id
     * @param array $data
     * @author Qinii
     * @day 10/29/21
     */
    public function edit(int $id, array $data)
    {
        event('community.update.before',compact('id','data'));
        if ($data['topic_id']) {
            $getTopic  =  app()->make(CommunityTopicRepository::class)->get($data['topic_id']);

            if (!$getTopic || !$getTopic->status) throw new ValidateException('话题不存在或已关闭');
            $data['category_id'] = $getTopic->category_id;
        }

        Db::transaction(function () use($id, $data) {
            $spuId = $data['spu_id'];
            unset($data['spu_id']);
            $community = $this->dao->update($id, $data);
            if ($spuId) $this->joinProduct($id, $spuId);
            event('community.update.before',compact('id','community'));
        });
    }

    public function joinProduct($id, array $data)
    {
        $make = app()->make(RelevanceRepository::class);
        $data = array_unique($data);
        $res = [];
        foreach ($data as $value) {
            if ($value) {
                $res[] = [
                    'left_id' => $id,
                    'right_id' => $value,
                    'type' => RelevanceRepository::TYPE_COMMUNITY_PRODUCT
                ];
            }
        }
        $make->clear($id,RelevanceRepository::TYPE_COMMUNITY_PRODUCT,'left_id');
        if($res) $make->insertAll($res);
    }

    /**
     * TODO 获取某用户信息
     * @param int $uid
     * @param null $self
     * @return mixed
     * @author Qinii
     * @day 10/29/21
     */
    public function getUserInfo(int $uid, $self = null)
    {
        $relevanceRepository = app()->make(RelevanceRepository::class);
        $data['focus'] = $relevanceRepository->getFieldCount('left_id', $uid,RelevanceRepository::TYPE_COMMUNITY_FANS);


        $is_start = $is_self = false;
        if ($self && $self->uid == $uid) {
            $user = $self;
            $is_self = true;
        } else {
            $user = app()->make(UserRepository::class)->get($uid);
            $is_start = $relevanceRepository->checkHas($self->uid, $uid, RelevanceRepository::TYPE_COMMUNITY_FANS) > 0;
        }
        $data['start'] = $user->count_start;
        $data['uid']   = $user->uid;
        $data['avatar'] = $user->avatar;
        $data['nickname'] = $user->nickname;
        $data['is_start'] = $is_start;
        $data['member_icon'] = systemConfig('member_status') ? ($user->member->brokerage_icon ?? '')  : '';
        $data['is_self'] = $is_self;
        $data['fans'] = $user->count_fans;

        return $data;
    }

    public function setFocus(int $id, int  $uid,int $status)
    {
        $make = app()->make(RelevanceRepository::class);
        $check  = $make->checkHas($uid, $id, RelevanceRepository::TYPE_COMMUNITY_FANS);
        if ($status) {
            if ($check)  throw new ValidateException('您已经关注过他了～');
            $make->create($uid, $id,RelevanceRepository::TYPE_COMMUNITY_FANS,true);
            app()->make(UserRepository::class)->incField($id, 'count_fans', 1);
        } else {
            if (!$check) throw new ValidateException('您还未关注他哦～');
            $make->destory($uid, $id,RelevanceRepository::TYPE_COMMUNITY_FANS);
            app()->make(UserRepository::class)->decField($id, 'count_fans', 1);
        }
        return ;
    }

    public function form($id)
    {
        $form = Elm::createForm(Route::buildUrl('systemCommunityUpdate', ['id' => $id])->build());
        $data = $this->dao->get($id);
        if (!$data) throw new ValidateException('数据不存在');
        $formData = $data->toArray();

        return $form->setRule([
            Elm::rate('start', '排序星级')->max(5)
        ])->setTitle('编辑星级')->formData($formData);
    }

    public function showForm($id)
    {
        $form = Elm::createForm(Route::buildUrl('systemCommunityStatus', ['id' => $id])->build());
        $data = $this->dao->get($id);
        if (!$data) throw new ValidateException('数据不存在');
        return $form->setRule([
            Elm::hidden('status', -1),
            Elm::textarea('refusal', '下架理由', '信息存在违规')->required()
        ])->setTitle('强制下架');
    }

    public function setCommunityStart(int $id, $userInfo, int $status)
    {
        $make = app()->make(RelevanceRepository::class);
        $userRepository = app()->make(UserRepository::class);

        if ($status) {
            $res = $make->create($userInfo->uid, $id, RelevanceRepository::TYPE_COMMUNITY_START,true);
            if (!$res) throw new ValidateException('您已经点赞过了');

            $ret = $this->dao->get($id);
            $user = $userRepository->get($ret['uid']);
            $this->dao->incField($id,'count_start',1);
            if ($user) $userRepository->incField((int)$user->uid,'count_start',1);
        }
        if (!$status) {
            if (!$make->checkHas($userInfo->uid, $id, RelevanceRepository::TYPE_COMMUNITY_START))
                throw new ValidateException('您还没有点赞呢～');
            $make->destory($userInfo->uid, $id, RelevanceRepository::TYPE_COMMUNITY_START);

            $ret = $this->dao->get($id);
            $user = $userRepository->get($ret['uid']);
            $this->dao->decField($id,'count_start',1);
            if ($user)  $userRepository->decField((int)$user->uid, 'count_start',1);
        }
    }

    public function setStatus($id, $data)
    {
        $ret = $this->dao->get($id);
        event('community.status.before',compact('id','data'));
        Db::transaction(function () use($ret,$id, $data) {

            if ($data['status'] == 1) {
                $make = app()->make(UserBrokerageRepository::class);
                $make->incMemberValue($ret['uid'], 'member_community_num', $id);
            }
            $data['status_time'] = date('Y-m-d H:i;s', time());
            $this->dao->update($id, $data);
            event('community.status',compact('id'));
        });

    }

    public function destory($id, $user = null)
    {
        event('community.delete.before',compact('id','user'));
        $this->dao->update($id,  ['is_del' =>  1]);
        event('community.delete',compact('id', 'user'));
    }

    public function getDataBySpu($spuId)
    {
        $where = array_merge(['spu_id' => $spuId], self::IS_SHOW_WHERE);
        return $this->dao->getSearch($where)
            ->order('create_time DESC')
            ->field('community_id,title,image,is_type')
            ->limit(3)->select();
    }

    public function qrcode($id, $type,$user)
    {
        $res = $this->dao->search(['is_type' => self::COMMUNIT_TYPE_VIDEO,'community_id' => $id, 'status' => 1, 'is_show' => 1])->find();
        if (!$res) return false;
        $make = app()->make(QrcodeService::class);
        if ($type == 'routine') {
            $name = md5('rcwx' . $id . $type . $user->uid . $user['is_promoter'] . date('Ymd')) . '.jpg';
            $params = 'id=' . $id . '&spid=' . $user['uid'];
            $link = 'pages/short_video/nvueSwiper/index';
            return $make->getRoutineQrcodePath($name, $link, $params);
        } else {
            $name = md5('cwx' . $id . $type . $user->uid . $user['is_promoter'] . date('Ymd')) . '.jpg';
            $link = 'pages/short_video/nvueSwiper/index';
            $link = $link . '?id=' . $id . '&spid=' . $user['uid'];
            $key = 'com' . $type . '_' . $id . '_' . $user['uid'];
            return $make->getWechatQrcodePath($name, $link, false, $key);
        }
    }
}

