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

namespace app\controller\merchant\store;

use app\common\repositories\store\ExcelRepository;
use app\common\repositories\store\StorePrinterRepository;
use crmeb\exceptions\UploadException;
use crmeb\services\ExcelService;
use think\App;
use crmeb\basic\BaseController;

class StorePrinter extends BaseController
{

    protected $repository;

    public function __construct(App $app, StorePrinterRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * TODO
     * @return mixed
     * @author Qinii
     * @day 2020-08-15
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['status','keyword']);
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->merList($where,$page,$limit);
        return app('json')->success($data);
    }

    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form(null)));
    }

    public function create()
    {
        $params = $this->request->params([
            'printer_name',
            'printer_appkey',
            'printer_appid',
            'printer_secret',
            'printer_terminal',
            'status',
        ]);

        if (!$params['printer_name'] ||
            !$params['printer_appkey'] ||
            !$params['printer_appid'] ||
            !$params['printer_secret'] ||
            !$params['printer_terminal']
        ) {
            return app('json')->fail('信息不完整');
        }
        $params['mer_id']  = $this->request->merId();
        $this->repository->create($params);
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        return app('json')->success(formToData($this->repository->form($id)));
    }

    public function update($id)
    {
        $params = $this->request->params([
            'printer_name',
            'printer_appkey',
            'printer_appid',
            'printer_secret',
            'printer_terminal',
            'status',
        ]);

        if (!$params['printer_name'] ||
            !$params['printer_appkey'] ||
            !$params['printer_appid'] ||
            !$params['printer_secret'] ||
            !$params['printer_terminal']
        ) {
            return app('json')->fail('信息不完整');
        }
        $res = $this->repository->getWhere(['printer_id' => $id, 'mer_id' => $this->request->merId()]);
        if (!$res) return app('json')->fail('打印机信息不存在');
        $this->repository->update($id, $params);
        return app('json')->success('添加成功');
    }

    public function delete($id)
    {
        $res = $this->repository->getWhere(['printer_id' => $id, 'mer_id' => $this->request->merId()]);
        if (!$res) return app('json')->fail('打印机信息不存在');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    public function switchWithStatus($id)
    {
        $status = $this->request->param('status') == 1 ? 1 : 0;
        $this->repository->update($id,['status' => $status]);
        return app('json')->success('修改成功');
    }

}
