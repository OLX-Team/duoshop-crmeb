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

namespace app\controller\admin\system\serve;

//use app\validate\admin\serve\ServeValidata;
//use app\services\system\config\SystemConfigServices;
use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use app\validate\admin\ExpressValidata;
use app\validate\admin\MealValidata;
use crmeb\basic\BaseController;
use crmeb\services\CrmebServeServices;
use think\App;
use think\facade\Cache;
use app\validate\admin\CrmebServeValidata;

/**
 * Class Serve
 * @package app\controller\admin\v1\serve
 */
class Serve extends BaseController
{
    protected $services;
    /**
     * Serve constructor.
     * @param App $app
     * @param ServeServices $services
     */
    public function __construct(App $app, CrmebServeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 检测登录
     * @return mixed
     */
    public function is_login()
    {
        $sms_info = Cache::get('serve_account');
        if ($sms_info) {
            return app('json')->success(['status' => true, 'info' => $sms_info]);
        } else {
            return app('json')->success(['status' => false]);
        }
    }

    /**
     * 获取套餐列表
     * @param string $type 套餐类型：sms,短信；query,物流查询；dump,电子面单；copy,产品复制
     * @return mixed
     */
    public function mealList(string $type)
    {
        $res = $this->services->user()->mealList($type);
        if ($res) {
            return app('json')->success($res);
        } else {
            return app('json')->fail('获取套餐列表失败');
        }
    }

    /**
     * 获取支付码
     * @return mixed
     */
    public function payMeal()
    {
        $data = $this->request->params(['meal_id','price','num','type','pay_type']);
        $openInfo = $this->services->user()->getUser();
        if (!$openInfo) app('json')->fail('获取支付码失败');
        switch ($data['type']) {
            case "sms" :
                if (!$openInfo['sms']['open']) return app('json')->fail('请先开通短信服务');
                break;
            case "query" :
                if (!$openInfo['query']['open']) return app('json')->fail('请先开通物流查询服务');
                break;
            case "dump" :
                if (!$openInfo['dump']['open']) return app('json')->fail('请先开通电子面单打印服务');
                break;
            case "copy" :
                if (!$openInfo['copy']['open']) return app('json')->fail('请先开通商品采集服务');
                break;
        }
        $this->validate($data, MealValidata::class);

        $res = $this->services->user()->payMeal($data);
        if ($res) {
            return app('json')->success($res);
        } else {
            return app('json')->fail('获取支付码失败');
        }
    }

    /**
     * 获取用户信息，用户信息内包含是否开通服务字段
     * @return mixed
     */
    public function getUserInfo()
    {
        return app('json')->success($this->services->user()->getUser());
    }

    /**
     * 查询使用记录
     * @return mixed
     */
    public function getRecord()
    {
        [$page, $limit] = $this->getPage();
        $data = $this->request->params(['type']);
        return app('json')->success($this->services->user()->record($page, $limit, $data['type']));
    }

    /**
     * 开通服务
     * @param string $type 套餐类型：sms,短信；query,物流查询；dump,电子面单；copy,产品复制
     * @return mixed
     */
    public function openServe()
    {
        $type = $this->request->param('type');
        switch ($type)
        {
            case 'sms': //短信呢
                $sign = $this->request->param('sign');
                if(!$sign)  return app('json')->fail('请设置短信签名');
                $this->services->sms()->setSign($sign)->open();
                break;
            case 'query':
                $this->services->express()->open();
                break;
            case 'dump':
                $this->services->express()->open();
                break;
            case 'copy':
                $this->services->copy()->open();
                break;
        }
        return app('json')->success('开通成功');
    }

    /**
     * 修改密码
     * @return mixed
     */
    public function changePassword(CrmebServeValidata $validata)
    {

        $data = $this->request->params(['phone','account','password','verify_code']);
        $validata->check($data);
        $data['password'] = md5($data['password']);
        $this->services->user()->modify($data);
        Cache::delete('serve_account');
        return app('json')->success('修改成功');
    }

    /**
     * 修改手机号
     * @return mixed
     */
    public function updatePhone(CrmebServeValidata $validata)
    {
        $data = $this->request->params(['phone','account','verify_code']);
        $validata->scene('phone')->check($data);

        $this->services->user()->modifyPhone($data);
        Cache::delete('sms_account');
        return app('json')->success('修改成功');
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 7/17/21
     */
    public function getConfig()
    {
        $params = [
            'express_app_code' => systemConfig('express_app_code'),
            'crmeb_serve_express' => systemConfig('crmeb_serve_express'),
            'crmeb_serve_dump' => systemConfig('crmeb_serve_dump'),
            'copy_product_status' => systemConfig('copy_product_status'),
            'copy_product_apikey' => systemConfig('copy_product_apikey'),
        ];

        return app('json')->success($params);
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 7/17/21
     */
    public function setConfig()
    {
        $params = $this->request->params([
            'express_app_code',
            'crmeb_serve_express',
            'crmeb_serve_dump',
            'copy_product_status',
            'copy_product_apikey'
        ]);
        app()->make(ConfigValueRepository::class)->setFormData($params,0);
        return app('json')->success('保存成功');
    }

    /**
     * TODO 购买记录
     * @return \think\response\Json
     * @author Qinii
     * @day 7/22/21
     */
    public function paylst()
    {
        [$page, $limit] = $this->getPage();
        $data = $this->services->user()->userBill($page, $limit);
        return app('json')->success($data);
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 7/22/21
     */
    public function merPaylst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['type','mer_id','date']);
        $data = app()->make(ServeOrderRepository::class)->getList($where,$page,$limit);
        return app('json')->success($data);
    }

    public function merLst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['mer_id','date']);
        $data = app()->make(MerchantRepository::class)->lst($where, $page, $limit);
        return app('json')->success($data);
    }
}
