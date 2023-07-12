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
use app\common\repositories\system\RelevanceRepository;
use crmeb\basic\BaseController;
use crmeb\services\MiniProgramService;
use think\App;
use app\common\repositories\community\CommunityReplyRepository as repository;
use think\exception\ValidateException;

class CommunityReply extends BaseController
{
    /**
     * @var CommunityReplyRepository
     */
    protected $repository;

    /**
     * User constructor.
     * @param App $app
     * @param  $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        if (!systemConfig('community_status')) throw  new ValidateException('未开启社区功能');
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function lst($id)
    {
        if (!systemConfig('community_reply_status'))
            return app('json')->success([
                'count' => 0,
                'all' => 0,
                'start' => 0,
                'list' => []
            ]);
        $where['community_id'] = $id;
        [$page, $limit] = $this->getPage();
        $userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
        return app('json')->success($this->repository->getApiList($where, $page, $limit, $userInfo));
    }

    /**
     * TODO 发评论
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 10/29/21
     */
    public function create($id)
    {
        if (!systemConfig('community_reply_status'))
            return app('json')->fail('评论功能未开启');
        if (systemConfig('community_reply_auth') && !$this->request->userInfo()->phone)
            return app('json')->fail('请先绑定手机号');

        $replyId = $this->request->param('reply_id', 0);

        $data = $this->request->params(['content']);
        if (empty($data['content'])) return app('json')->fail('请输入回复内容');
        MiniProgramService::create()->msgSecCheck($this->request->userInfo(), $data['content'],2,0);
        $data['uid'] = $this->request->userInfo()->uid;
        $data['community_id'] = $id;

        $data['status'] = 1;
        $msg = '回复成功';
        if (systemConfig('community_reply_audit')) {
            $data['status'] = 0;
            $msg = '回复成功,正在审核中';
        }
        $ret = $this->repository->create($replyId, $data);
        return app('json')->success($msg, $ret);
    }

    public function delete($id)
    {
        if (!$this->repository->uidExists($id, $this->request->userInfo()->uid))
            return app('json')->fail('评论不存在');
        $this->repository->delete($id);
        return app('json')->success('评论删除');
    }

    /**
     * TODO 评论点赞
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 10/29/21
     */
    public function start($id)
    {
        if (!systemConfig('community_reply_status'))
            return app('json')->success('评论不存在');
        $status = $this->request->param('status') == 1 ? 1 : 0;
        if (!$this->repository->exists($id))
            return app('json')->fail('评论不存在');

        $uid = $this->request->userInfo()->uid;
        $this->repository->setStart($id, $uid, $status);
        if ($status) {
            return app('json')->success('点赞成功');
        } else {
            return app('json')->success('取消点赞');
        }
    }
}
