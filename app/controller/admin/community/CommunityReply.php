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

namespace app\controller\admin\community;

use crmeb\basic\BaseController;
use think\App;
use app\common\repositories\community\CommunityReplyRepository as repository;

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
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function lst()
    {
        $where = $this->request->params(['keyword', 'date', 'username', 'community_id', 'pid']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }


    /**
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function delete($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_del' => 1]);
        return app('json')->success('删除成功');
    }

    public function statusForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->statusForm($id)));
    }


    public function switchStatus($id)
    {
        $data = $this->request->params(['status', 'refusal']);

        if (!in_array($data['status'], [1, -1]))
            return app('json')->fail('审核类型错误');

        if ($data['status'] == -1 && empty($data['refusal']))
            return app('json')->fail('请填写拒绝理由');

        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id, $data);
        return app('json')->success('审核成功');
    }
}
