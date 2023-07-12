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

use app\common\dao\community\CommunityReplyDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\RelevanceRepository;
use Carbon\Exceptions\InvalidDateException;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;

class CommunityReplyRepository extends BaseRepository
{
    /**
     * @var CommunityReplyDao
     */
    protected $dao;

    /**
     * CommunityReplyRepository constructor.
     * @param CommunityReplyDao $dao
     */
    public function __construct(CommunityReplyDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, int $page, int $limit)
    {
        $where['is_del'] = 0;
        $query = $this->dao->search($where)->with([
            'community' => function ($query) {
                $query->field('community_id,title');
            },
            'author' => function ($query) {
                $query->field('uid,nickname,avatar');
            },
            'reply' => function ($query) {
                $query->field('uid,nickname,avatar');
            },
            'hasReply' => function ($query) {
                $query->field('pid, reply_id, status');
            },
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list');
    }


    public function getApiList(array $where, int $page, int $limit, $userInfo)
    {
        $make = app()->make(CommunityRepository::class);

        $where_['community_id'] = $where['community_id'];
        $where_ = CommunityRepository::IS_SHOW_WHERE;

        if ($userInfo && $make->uidExists((int)$where['community_id'], $userInfo->uid)) {
            $where_ = ['is_del' => 0];
        }

        $where_['community_id'] = $where['community_id'];

        if (!$make->getWhereCount($where_)) throw new ValidateException('内容不存在，可能被删删除了哦～');

        $where['status'] = 1;
        $all = $this->dao->getSearch($where)->count();
        $start = $this->dao->getSearch($where)->sum('count_start');
        $where['pid'] = 0;

        $query = $this->dao->getSearch($where)
            ->order('create_time DESC')
            ->hidden(['refusal'])
            ->with([
                'author' => function ($query) {
                    $query->field('uid,nickname,avatar');
                },
                'is_start' => function ($query) use ($userInfo) {
                    $query->where('left_id', $userInfo->uid ?? null);
                },
                'children' => [
                    'author' => function ($query) {
                        $query->field('uid,nickname,avatar');
                    },
                    'reply' => function ($query) {
                        $query->field('uid,nickname,avatar')->order('create_time ASC');
                    },
                    'is_start' => function ($query) use ($userInfo) {
                        $query->where('left_id', $userInfo->uid ?? null);
                    }
                ],
            ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('all', 'start', 'count', 'list');
    }


    /**
     * TODO 发表评论
     * @param int $replyId
     * @param array $data
     * @author Qinii
     * @day 10/29/21
     */
    public function create(int $replyId, array $data)
    {
        $make = app()->make(CommunityRepository::class);

        if (!$make->exists($data['community_id']))
            throw  new ValidateException('内容不存在，可能已被删除了哦～');

        $data['pid'] = $replyId;
        if ($replyId) {
            $get = $this->dao->get($replyId);
            if (!$get) throw  new ValidateException('您回复的评论不存在');
            if ($get->pid) {
                $data['re_uid'] = $get->uid;
                $data['pid'] = $get->pid;
            }
        }

        $res = Db::transaction(function () use ($replyId, $data, $make) {
            $res = $this->dao->create($data);
            if ($replyId) $this->dao->incField($data['pid'], 'count_reply', 1);
            return $res;
        });

        $ret = $this->dao->getWhere(['reply_id' => $res->reply_id], '*', [
            'author' => function ($query) {
                $query->field('uid,nickname,avatar');
            },
            'reply' => function ($query) {
                $query->field('uid,nickname,avatar')->order('create_time ASC');
            },
        ]);
        return $ret;
    }

    public function delete($id)
    {
        Db::transaction(function () use ($id) {
            $get = $this->dao->get($id);
            $make = app()->make(CommunityRepository::class);
            if ($get->pid) $this->dao->decField($get['pid'], 'count_reply', 1);
            $make->decField($get['community_id'], 'count_reply', 1);
            $get->delete();
        });
    }


    public function setStart(int $id, int  $uid, int $status)
    {
        $make = app()->make(RelevanceRepository::class);
        $check  = $make->checkHas($uid, $id, RelevanceRepository::TYPE_COMMUNITY_REPLY_START);
        if ($status) {
            if ($check)  throw new ValidateException('您已经赞过过他了～');
            $make->create($uid, $id, RelevanceRepository::TYPE_COMMUNITY_REPLY_START, true);
            $this->dao->incField($id, 'count_start', 1);
        } else {
            if (!$check) throw new ValidateException('您还未赞过他哦～');
            $make->destory($uid, $id, RelevanceRepository::TYPE_COMMUNITY_REPLY_START);
            $this->dao->decField($id, 'count_start', 1);
        }
        return;
    }


    public function statusForm(int $id)
    {
        $formData = $this->dao->get($id)->toArray();

        if ($formData['status'] !== 0) throw new ValidateException('请勿重复审核');
        $form = Elm::createForm(Route::buildUrl('systemCommunityReplyStatus', ['id' => $id])->build());

        $form->setRule([
            Elm::textarea('content', '评论内容')->disabled(true),

            Elm::radio('status', '审核状态', 1)->options([
                ['value' => -1, 'label' => '未通过'],
                ['value' => 1, 'label' => '通过']]
            )->control([
                ['value' => -1, 'rule' => [
                    Elm::textarea('refusal', '未通过原因', '')->required()
                ]]
            ]),
        ]);
        $formData['status'] = 1;
        return $form->setTitle('审核评论')->formData($formData);
    }
}
