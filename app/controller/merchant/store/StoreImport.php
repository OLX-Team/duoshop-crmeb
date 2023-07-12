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
use app\common\repositories\store\order\StoreImportDeliveryRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use crmeb\jobs\ImportSpreadsheetExcelJob;
use crmeb\services\ExcelService;
use crmeb\services\SpreadsheetExcelService;
use crmeb\services\UploadService;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\order\StoreImportRepository;

use think\facade\Queue;

class StoreImport extends BaseController
{
    protected $repository;

    /**
     * Product constructor.
     * @param App $app
     * @param StoreImportRepository $repository
     */
    public function __construct(App $app, StoreImportRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['status','date',['import_type','delivery'],'type']);
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->getList($where,$page,$limit);
        return app('json')->success($data);
    }

    public function detail($id)
    {
        $where = [
            'import_id' => $id,
            'mer_id' => $this->request->merId()
        ];
        [$page, $limit] = $this->getPage();
        $data = app()->make(StoreImportDeliveryRepository::class)->getList($where,$page, $limit);
        return app('json')->success($data);
    }

    public function export($id)
    {
        $where = [
            'import_id' => $id,
            'mer_id' => $this->request->merId()
        ];
        [$page, $limit] = $this->getPage();
        $data = app()->make(ExcelService::class)->importDelivery($where, $page, $limit);
        return app('json')->success($data);
    }

    /**
     * TODO 导入excel信息
     * @return \think\response\Json
     * @author Qinii
     * @day 3/16/21
     */
    public function Import($type)
    {
        $file = $this->request->file('file');
        if (!$file)  return app('json')->fail('请上传EXCEL文件');
        $file = is_array($file) ? $file[0] : $file;
        validate(["file|文件" => ['fileExt' => 'xlsx,xls',]])->check(['file' => $file]);

        $upload = UploadService::create(1);
        $ret = $upload->to('excel')->move('file');
        if ($ret === false) return app('json')->fail($upload->getError());
        $res = $upload->getUploadInfo();
        $path = rtrim(public_path(),'/').$res['dir'];
        $data = [];
        switch ($type){
             case 'delivery' :
                 SpreadsheetExcelService::instance()->checkImport($path,['E3' => '物流单号']);
                 $data = [
                     'mer_id' => $this->request->merId(),
                     'data' => [
                         'path' => $path,
                         'sql' => ['delivery_name' => 'D', 'delivery_id' => 'E',],
                         'where' => ['order_sn' => 'B',],
                    ]
                 ];
                break;
            default:
                $data = SpreadsheetExcelService::instance()->_import($path,[],[],0);
                break;
        }
        if(!empty($data)){
            $res = $this->repository->create($this->request->merId(),'delivery');
            $data['data']['import_id'] = $res->import_id;

//            app()->make(StoreOrderRepository::class)->setWhereDeliveryStatus($data['data'],$data['mer_id']);

            Queue::push(ImportSpreadsheetExcelJob::class,$data);
            return app('json')->success('开始导入数据，请稍后在批量发货记录中查看！');
        }
        return app('json')->fail('数据类型错误');
    }
}

