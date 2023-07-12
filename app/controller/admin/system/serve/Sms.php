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

use app\validate\admin\CrmebServeValidata;
use crmeb\basic\BaseController;
use crmeb\services\CrmebServeServices;
use think\App;

/**
 * Class Sms
 * @package app\controller\admin\v1\serve
 */
class Sms extends BaseController
{
    protected $services;
    /**
     * Sms constructor.
     * @param App $app
     * @param ServeServices $services
     */
    public function __construct(App $app, CrmebServeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 修改短信签名
     * @param string $sign
     * @return mixed
     */
    public function changeSign(CrmebServeValidata $validata)
    {
        $data = $this->request->params(['phone','sign','verify_code']);

        $validata->scene('phone')->check(['phone' => $data['phone']]);

        if (!$data['sign']) {
            return app('json')->fail('请设置短信签名');
        }
        $this->services->sms()->modify($data['sign'], $data['phone'], $data['verify_code']);
        return app('json')->success('修改短信签名成功');
    }

    /**
     * 获取短信模板
     * @return mixed
     */
    public function temps()
    {
        [$page, $limit] = $this->getPage();
        $type = $this->request->param('type');

        return app('json')->success($this->services->getSmsTempsList((int)$page, (int)$limit, (int)$type));
    }

    /**
     * 申请模板
     * @return mixed
     */
    public function apply()
    {
        $data = $this->request->params(['title','type','content']);
        if (!$data['title'] || !$data['content'] || !$data['type']) {
            return app('json')->success('请填写申请模板内容');
        }
        $ret = $this->services->sms()->apply($data['title'], $data['content'], (int)$data['type']);
        return app('json')->success($ret);
    }

    /**
     * 获取申请记录
     * @return mixed
     */
    public function applyRecord()
    {
        [$page, $limit] = $this->getPage();
        $tempType = $this->request->param('temp_type',0);
        if (is_null($tempType)) $tempType = 0;
        $ret = $this->services->sms()->applys((int)$tempType, $page, $limit);

        return app('json')->success($ret);
    }
}
