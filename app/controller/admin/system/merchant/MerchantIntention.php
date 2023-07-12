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


namespace app\controller\admin\system\merchant;

use app\common\repositories\store\ExcelRepository;
use app\common\repositories\system\CacheRepository;
use crmeb\services\ExcelService;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantIntentionRepository;

class MerchantIntention extends BaseController
{
    protected $repository;

    /**
     * MerchantIntention constructor.
     * @param App $app
     * @param MerchantIntentionRepository $repository
     */
    public function __construct(App $app, MerchantIntentionRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['mer_name', 'status', 'date', 'keyword', 'mer_intention_id', 'category_id', 'type_id']);
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function form($id)
    {
        if (!$this->repository->getWhereCount(['mer_intention_id' => $id, 'is_del' => 0]))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->markForm($id)));
    }

    public function statusForm($id)
    {
        if (!$this->repository->getWhereCount(['mer_intention_id' => $id, 'is_del' => 0]))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->statusForm($id)));
    }

    public function mark($id)
    {
        if (!$this->repository->getWhereCount(['mer_intention_id' => $id, 'is_del' => 0]))
            return app('json')->fail('数据不存在');
        $data = $this->request->param('mark');
        $this->repository->update($id, ['mark' => $data]);
        return app('json')->success('修改成功');
    }

    public function switchStatus($id)
    {
        if (!$this->repository->getWhereCount(['mer_intention_id' => $id, 'is_del' => 0]))
            return app('json')->fail('数据不存在');
        $data = $this->request->params(['status', 'fail_msg', 'create_mer']);
        $data['status'] = $data['status'] == 1 ? 1 : 2;
        $this->repository->updateStatus($id, $data);
        return app('json')->success('修改成功');
    }

    public function delete($id)
    {
        if (!$this->repository->getWhereCount(['mer_intention_id' => $id, 'is_del' => 0]))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_del' => 1]);
        return app('json')->success('删除成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/9/15
     * @return mixed
     */
    public function saveAgree()
    {
        $agree = $this->request->param('agree');
        app()->make(CacheRepository::class)->save('sys_intention_agree', $agree);
        return app('json')->success('保存成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/9/15
     * @return mixed
     */
    public function getAgree()
    {
        $make = app()->make(CacheRepository::class);
        return app('json')->success(['sys_intention_agree' => $make->getResult('sys_intention_agree')]);
    }

    public function excel()
    {
        $where = $this->request->params(['mer_name', 'status', 'date', 'keyword', 'mer_intention_id', 'category_id', 'type_id']);

        [$page, $limit] = $this->getPage();
        $data = app()->make(ExcelService::class)->intention($where,$page,$limit);
        return app('json')->success($data);

    }

}
