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

namespace app\controller\api\community;

use app\common\repositories\community\CommunityRepository;
use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\system\RelevanceRepository;
use app\common\repositories\user\UserHistoryRepository;
use app\common\repositories\user\UserRelationRepository;
use app\common\repositories\user\UserRepository;
use app\validate\api\CommunityValidate;
use crmeb\basic\BaseController;
use crmeb\services\MiniProgramService;
use think\App;
use app\common\repositories\community\CommunityRepository as repository;
use think\exception\ValidateException;

class Community extends BaseController
{
    /**
     * @var CommunityRepository
     */
    protected $repository;
    protected $user;

    /**
     * User constructor.
     * @param App $app
     * @param  $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->user = $this->request->isLogin() ? $this->request->userInfo() : null;
        if (!systemConfig('community_status') ) throw  new ValidateException('未开启社区功能');
    }

    /**
     * TODO 文章列表
     * @return \think\response\Json
     * @author Qinii
     * @day 10/29/21
     */
    public function lst()
    {
        $where = $this->request->params(['keyword','topic_id','is_hot','category_id','spu_id']);
        if (!$where['category_id']) {
            unset($where['category_id']);
        } else  if ($where['category_id'] == -1) {
            $where['is_type'] = $this->repository::COMMUNIT_TYPE_VIDEO;
            unset($where['category_id']);
        }
        $where = array_merge($where,$this->repository::IS_SHOW_WHERE);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getApiList($where, $page, $limit, $this->user));
    }

    /**
     * TODO 视频列表
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/29
     */
    public  function videoShow()
    {
        [$page, $limit] = $this->getPage();
        if ($page == 1) $limit = $limit -1;
        $where['community_id'] = $this->request->param('id','');
        $where = array_merge($where,$this->repository::IS_SHOW_WHERE);
        return app('json')->success($this->repository->getApiVideoList($where, $page, $limit, $this->user));
    }

    /**
     * TODO  关注的人的文章
     * @param RelevanceRepository $relevanceRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 11/2/21
     */
    public function focuslst(RelevanceRepository $relevanceRepository)
    {
        $where = $this->repository::IS_SHOW_WHERE;
        $where_ = [
            'left_id' => $this->user->uid ?? null ,
            'type'    => RelevanceRepository::TYPE_COMMUNITY_FANS,
        ];
        $where['uids'] = $relevanceRepository->getSearch($where_)->column('right_id');
        [$page, $limit] = $this->getPage();
        if ($page == 1) $limit = $limit -1;
        $type = $this->request->param('type');
        if ($type) $where['is_type'] = $this->repository::COMMUNIT_TYPE_VIDEO;
        return app('json')->success($this->repository->getApiList($where, $page, $limit, $this->user));
    }

    /**
     * TODO 某个用户的文章
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 10/29/21
     */
    public function userCommunitylst($id)
    {
        $where = [];
        if (!$this->user || $this->user->uid !=  $id) {
            $where = $this->repository::IS_SHOW_WHERE;
        }
        $where['uid'] = $id;
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getApiList($where, $page, $limit, $this->user));
    }

    /**
     * TODO 某个用户的视频
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 10/29/21
     */
    public function userCommunityVideolst($id)
    {
        $where = [];
        [$page, $limit] = $this->getPage();
        $is_start = $this->request->param('is_star',0);
        if ($is_start) {
            //某人赞过的视频
            $where = $this->repository::IS_SHOW_WHERE;
        } else {
            //某个人的视频
            if (!$this->user || $this->user->uid !=  $id) {
                $where =$this->repository::IS_SHOW_WHERE;
            }
            $where['uid'] = $id;
        }
        $where['is_del'] = 0;
        $where['community_id'] = $this->request->param('community_id','');

        $data = $this->repository->getApiVideoList($where, $page, $limit, $this->user,$is_start);
        return app('json')->success($data);
    }


    /**
     * TODO 我赞过的文章
     * @param RelevanceRepository $relevanceRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 10/28/21
     */
    public function getUserStartCommunity(RelevanceRepository $relevanceRepository)
    {
        [$page, $limit] = $this->getPage();
        $where['uid'] = $this->user->uid;
        $data = $relevanceRepository->getUserStartCommunity($where,$page, $limit);
        return app('json')->success($data);
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function show($id)
    {
        return app('json')->success($this->repository->show($id, $this->user));
    }

    /**
     * TODO 已购商品
     * @return \think\response\Json
     * @author Qinii
     * @day 10/28/21
     */
    public function payList()
    {
        [$page, $limit] = $this->getPage();
        $keyword = $this->request->param('keyword');
        $data = app()->make(StoreOrderProductRepository::class)->getUserPayProduct($keyword, $this->user->uid, $page, $limit);
        return app('json')->success($data);
    }

    /**
     * TODO 收藏商品
     * @return \think\response\Json
     * @author Qinii
     * @day 10/28/21
     */
    public function relationList()
    {
        [$page, $limit] = $this->getPage();
        $keyword = $this->request->param('keyword');
        $data = app()->make(UserRelationRepository::class)->getUserProductToCommunity($keyword, $this->user->uid, $page, $limit);
        return app('json')->success($data);
    }

    public function historyList()
    {
        [$page, $limit] = $this->getPage();
        $where['keyword'] = $this->request->param('keyword');
        $where['uid'] = $this->request->userInfo()->uid;
        $where['type'] = 1;
        $data = app()->make(UserHistoryRepository::class)->historyLst($where, $page,$limit);
        return app('json')->success($data);
    }

    /**
     * TODO 发布文章
     * @return \think\response\Json
     * @author Qinii
     * @day 10/29/21
     */
    public function create()
    {
        $data = $this->checkParams();
        $this->checkUserAuth();
        $data['uid'] = $this->request->uid();
        $res = $this->repository->create($data);
        return app('json')->success(['community_id' => $res]);
    }

    /**
     * TODO
     * @return bool|\think\response\Json
     * @author Qinii
     * @day 10/30/21
     */
    public function checkUserAuth()
    {
        $user = $this->request->userInfo();
        if ( systemConfig('community_auth') ) {
            if ($user->phone) {
                return true;
            }
            throw  new ValidateException('请先绑定您的手机号');
        } else {
            return true;
        }
    }


    /**
     * TODO 编辑
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 10/29/21
     */
    public function update($id)
    {
        $data = $this->checkParams();
        $this->checkUserAuth();
        if(!$this->repository->uidExists($id, $this->user->uid))
            return app('json')->success('内容不存在或不属于您');
        $this->repository->edit($id, $data);
        return app('json')->success(['community_id' => $id]);
    }

    public function checkParams()
    {
        $data = $this->request->params(['image','topic_id','content','spu_id','order_id',['is_type',1],'video_link']);
        $config = systemConfig(["community_app_switch",'community_audit','community_video_audit']);
        $data['status'] = 0;
        $data['is_show'] = 0;
        if ($data['is_type'] == 1) {
            if (!in_array($this->repository::COMMUNIT_TYPE_FONT,$config['community_app_switch']))
                throw new ValidateException('社区图文未开启');
            if ($config['community_audit']) {
                $data['status'] = 1;
                $data['is_show'] = 1;
                $data['status_time'] = date('Y-m-d H:i:s', time());
            }
        } else {
            if (!in_array($this->repository::COMMUNIT_TYPE_VIDEO,$config['community_app_switch']))
                throw new ValidateException('短视频未开启');
            if ($config['community_video_audit']) {
                $data['status'] = 1;
                $data['is_show'] = 1;
                $data['status_time'] = date('Y-m-d H:i:s', time());
            }
            if (!$data['video_link']) throw new ValidateException('请上传视频');

        }

        $data['content'] = filter_emoji($data['content']);
        MiniProgramService::create()->msgSecCheck($this->request->userInfo(), $data['content'],3,0);
        app()->make(CommunityValidate::class)->check($data);
        $arr = explode("\n", $data['content']);
        $title = rtrim(ltrim($arr[0]));
        if (mb_strlen($title) > 40 ){
            $data['title'] = mb_substr($title,0,30,'utf-8');
        } else {
            $data['title'] = $title;
        }
        if ($data['image']) $data['image'] = implode(',',$data['image']);
        return $data;
    }


    /**
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function delete($id)
    {
        if (!$this->repository->uidExists($id, $this->user->uid))
            return app('json')->fail('内容不存在或不属于您');
        $this->repository->destory($id, $this->user);

        return app('json')->success('删除成功');
    }

    /**
     * TODO 文章点赞/取消
     * @param $id
     * @param RelevanceRepository $relevanceRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 10/28/21
     */
    public function startCommunity($id)
    {
        $status = $this->request->param('status') == 1 ? 1 :0;
        if (!$this->repository->exists($id))
            return app('json')->fail('内容不存在');
        $this->repository->setCommunityStart($id, $this->user, $status);
        if ($status) {
            return app('json')->success('点赞成功');
        } else {
            return app('json')->success('取消点赞');
        }
    }

    /**
     * TODO 用户关注/取消
     * @param $id
     * @param RelevanceRepository $relevanceRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 10/28/21
     */
    public function setFocus($id)
    {
        $id  = (int)$id;
        $status  = $this->request->param('status') == 1 ? 1 :0;
        if ($this->user->uid == $id)
            return app('json')->fail('请勿关注自己');
        $make = app()->make(UserRepository::class);
        if (!$user = $make->get($id)) return app('json')->fail('未查询到该用户');

        $this->repository->setFocus($id, $this->user->uid, $status);

        if ($status) {
            return app('json')->success('关注成功');
        } else {
            return app('json')->success('取消关注');
        }
    }

    /**
     * TODO 我的粉丝
     * @param RelevanceRepository $relevanceRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 10/28/21
     */
    public function getUserFans(RelevanceRepository $relevanceRepository)
    {
        [$page, $limit] = $this->getPage();
        $fans = $relevanceRepository->getUserFans($this->user->uid, $page, $limit);
        return app('json')->success($fans);
    }

    /**
     * TODO 我的关注
     * @param RelevanceRepository $relevanceRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 10/28/21
     */
    public function getUserFocus(RelevanceRepository $relevanceRepository)
    {
        [$page, $limit] = $this->getPage();
        $start = $relevanceRepository->getUserFocus($this->user->uid, $page, $limit);
        return app('json')->success($start);
    }


    /**
     * TODO 用户信息
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 10/28/21
     */
    public function userInfo($id)
    {
        if (!$id)  return app('json')->fail('缺少参数');
        $data = $this->repository->getUserInfo($id, $this->user);
        return app('json')->success($data);
    }

    public function getSpuByOrder($id)
    {
        $data = $this->repository->getSpuByOrder($id,7233);
        return app('json')->success($data);
    }

    public function qrcode($id)
    {
        $id = (int)$id;
        $type = $this->request->param('type');
        if ($this->request->isLogin()) {
            $url = $this->repository->qrcode($id, $type, $this->request->userInfo());
        }
        if (!$url) return app('json')->fail('二维码生成失败');
        return app('json')->success(compact('url'));
    }
}
