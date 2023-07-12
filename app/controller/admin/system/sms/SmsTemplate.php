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


namespace app\controller\admin\system\sms;


use crmeb\basic\BaseController;
use crmeb\services\YunxinSmsService;
use FormBuilder\Exception\FormBuilderException;
use think\App;

/**
 * Class SmsTemplate
 * @package app\controller\admin\system\sms
 * @author xaboy
 * @day 2020-05-18
 */
class SmsTemplate extends BaseController
{
    /**
     * @var YunxinSmsService
     */
    protected $service;

    /**
     * Sms constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = YunxinSmsService::create();
    }

    /**
     * 异步获取公共模板列表
     */
    public function public()
    {
        $where = $this->request->params([
            ['is_have', ''],
            ['page', 1],
            ['limit', 20],
        ]);
        $templateList = $this->service->publictemp($where);
        if ($templateList['status'] == 400) return app('json')->fail($templateList['msg']);
        $arr = $templateList['data']['data'];
        foreach ($arr as $key => $value) {
            switch ($value['type']) {
                case 1:
                    $arr[$key]['type'] = '验证码';
                    break;
                case 2:
                    $arr[$key]['type'] = '通知';
                    break;
                case 3:
                    $arr[$key]['type'] = '推广';
                    break;
                default:
                    $arr[$key]['type'] = '';
                    break;
            }
        }
        $templateList['data']['data'] = $arr;
        return app('json')->success($templateList['data']);
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-18
     */
    public function form()
    {
        return app('json')->success(formToData($this->service->form()));
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function template()
    {
        $where = $this->request->params([
            ['status', ''],
            ['title', ''],
            ['temp_type', ''],
            ['page', 1],
            ['limit', 20]
        ]);
        $templateList = $this->service->template($where);
        if ($templateList['status'] == 400) return app('json')->fail($templateList['msg']);
        $arr = $templateList['data']['data'];
        foreach ($arr as $key => $value) {
            switch ($value['type']) {
                case 1:
                    $arr[$key]['type'] = '验证码';
                    break;
                case 2:
                    $arr[$key]['type'] = '通知';
                    break;
                case 3:
                    $arr[$key]['type'] = '推广';
                    break;
                default:
                    $arr[$key]['type'] = '';
                    break;
            }
        }
        $templateList['data']['data'] = $arr;
        return app('json')->success($templateList['data']);
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function apply()
    {
        $data = $this->request->params([
            'title',
            'content',
            ['type', 0]
        ]);
        if (!$data['title']) return app('json')->fail('请输入模板名称');
        if (!$data['content']) return app('json')->fail('请输入模板内容');
        $applyStatus = $this->service->apply($data['title'], $data['content'], $data['type']);
        if ($applyStatus['status'] == 400) return app('json')->fail($applyStatus['msg']);
        return app('json')->success('申请成功');
    }
}
