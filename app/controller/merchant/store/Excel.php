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
use crmeb\exceptions\UploadException;
use crmeb\services\ExcelService;
use think\App;
use crmeb\basic\BaseController;

class Excel extends BaseController
{

    protected $repository;

    public function __construct(App $app, ExcelRepository $repository)
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
        $admin = $this->request->adminInfo();
        if($admin['level']) $where['admin_id'] = $this->request->adminId();
        [$page, $limit] = $this->getPage();
        $where['type'] = $this->request->param('type','');
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->getList($where,$page,$limit);
        return app('json')->success($data);
    }

    /**
     * TODO 下载文件
     * @param $id
     * @return \think\response\File
     * @author Qinii
     * @day 2020-07-30
     */
    public function downloadExpress()
    {
        try{
            $file['name'] = 'express';
            $path = app()->getRootPath().'extend/express.xlsx';
            if(!$file || !file_exists($path)) return app('json')->fail('文件不存在');
            return download($path,$file['name']);
        }catch (UploadException $e){
            return app('json')->fail('下载失败');
        }
    }

    /**
     * TODO 所有类型
     * @return \think\response\Json
     * @author Qinii
     * @day 7/2/21
     */
    public function type()
    {
        $data = $this->repository->getTypeData();
        return app('json')->success($data);
    }

}
