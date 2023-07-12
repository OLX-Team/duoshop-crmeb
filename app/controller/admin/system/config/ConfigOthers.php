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

namespace app\controller\admin\system\config;

use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserRepository;
use crmeb\jobs\ChangeMerchantStatusJob;
use FormBuilder\Factory\Elm;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\system\config\ConfigClassifyRepository;
use app\common\repositories\system\config\ConfigRepository as repository;
use app\common\repositories\system\config\ConfigValueRepository;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Route;

class ConfigOthers extends BaseController
{

    public $repository;

    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function update()
    {
        $data = $this->request->params([
            'extension_status',
            'extension_two_rate',
            'extension_one_rate',
            'extension_self',
            'extension_limit',
            'extension_limit_day',
            'sys_extension_type',
            'lock_brokerage_timer',
            'max_bag_number',
            'promoter_explain',
            'user_extract_min',
            'withdraw_type'
        ]);

        if ($data['extension_two_rate'] < 0 || $data['extension_one_rate'] < 0)
            return app('json')->fail('比例不能小于0');
        if (bccomp($data['extension_one_rate'], $data['extension_two_rate'], 4) == -1)
            return app('json')->fail('一级比例不能小于二级比例');
        if (bccomp(bcadd($data['extension_one_rate'], $data['extension_two_rate'], 3), 1, 3) == 1)
            return app('json')->fail('比例之和不能超过1，即100%');
        if (!ctype_digit((string)$data['extension_limit_day']) || $data['extension_limit_day'] <= 0)
            return app('json')->fail('分销绑定时间必须大于0');

        $old = systemConfig(['extension_limit', 'extension_limit_day']);

        if (!$old['extension_limit'] && $data['extension_limit']) {
            app()->make(UserRepository::class)->initSpreadLimitDay(intval($data['extension_limit_day']));
        } else if ($old['extension_limit'] && !$data['extension_limit']) {
            app()->make(UserRepository::class)->clearSpreadLimitDay();
        } else if ($data['extension_limit_day'] != $old['extension_limit_day'] && $data['extension_limit']) {
            app()->make(UserRepository::class)->updateSpreadLimitDay(intval($data['extension_limit_day'] - $old['extension_limit_day']));
        }

        app()->make(ConfigValueRepository::class)->setFormData($data, 0);

        return app('json')->success('修改成功');
    }


    /**
     * TODO 拼团相关配置
     * @return \think\response\Json
     * @author Qinii
     * @day 4/6/22
     */
    public function getGroupBuying()
    {
        $data = [
            'ficti_status' => systemConfig('ficti_status'),
            'group_buying_rate' => systemConfig('group_buying_rate'),
        ];
        return app('json')->success($data);
    }

    public function setGroupBuying()
    {
        $data['ficti_status'] = $this->request->param('ficti_status') == 1 ? 1 : 0;
        $data['group_buying_rate'] = $this->request->param('group_buying_rate');
        if ($data['group_buying_rate'] < 0 || $data['group_buying_rate'] > 100)
            return app('json')->fail('请填写1～100之间的整数');
        app()->make(ConfigValueRepository::class)->setFormData($data, 0);

        return app('json')->success('修改成功');
    }

    public function getProfitsharing()
    {
        return app('json')->success(array_filter(systemConfig(['extract_maxmum_num', 'extract_minimum_line', 'extract_minimum_num', 'open_wx_combine', 'open_wx_sub_mch', 'mer_lock_time']), function ($val) {
                return $val !== '';
            }) + ['open_wx_sub_mch' => 0, 'open_wx_combine' => 0]);
    }

    public function setProfitsharing()
    {
        $data = $this->request->params(['extract_maxmum_num', 'extract_minimum_line', 'extract_minimum_num', 'open_wx_combine', 'open_wx_sub_mch', 'mer_lock_time']);
        if ($data['extract_minimum_num'] < $data['extract_minimum_line'])
            return app('json')->fail('最小提现额度不能小于最低提现金额');
        if ($data['extract_maxmum_num'] < $data['extract_minimum_num'])
            return app('json')->fail('最高提现额度不能小于最小提现额度');
        $config = systemConfig(['open_wx_combine', 'wechat_service_merid', 'wechat_service_key', 'wechat_service_v3key', 'wechat_service_client_cert', 'wechat_service_client_key', 'wechat_service_serial_no']);
        $open_wx_combine = $config['open_wx_combine'];
        unset($config['open_wx_combine']);
        if (($data['open_wx_combine'] || $data['open_wx_sub_mch']) && count(array_filter($config)) < 6) {
            return app('json')->fail('请先配置微信服务商相关参数');
        }
        Db::transaction(function () use ($data, $open_wx_combine) {
            app()->make(ConfigValueRepository::class)->setFormData($data, 0);
            if (!$open_wx_combine && $data['open_wx_combine']) {
                $column = app()->make(MerchantRepository::class)->search([])->where('sub_mchid', '')->column('mer_id');
                app()->make(MerchantRepository::class)->search([])->where('sub_mchid', '')->save(['mer_state' => 0]);
                foreach ($column as $merId) {
                    Queue::push(ChangeMerchantStatusJob::class, $merId);
                }
            }
        });
        return app('json')->success('修改成功');
    }


    /**
     *  未启用
     * TODO 上传图片水印设置
     * @return \think\response\Json
     * @author Qinii
     * @day 12/14/21
     */
    public function getImageWaterConfig()
    {
        $config = [
            'image_watermark_status',
            'watermark_type',
            'watermark_image',
            'watermark_opacity',
            'watermark_position',
            'watermark_rotate',
            'watermark_text',
            'watermark_text_angle',
            'watermark_text_color',
            'watermark_text_size',
            'watermark_x',
            'watermark_y'
        ];
        $formData = systemConfig($config);
        $form = Elm::createForm(Route::buildUrl('configOthersWaterSave')->build());
        $form->setRule([
            Elm::radio('image_watermark_status', '是否开启水印')
                ->setOptions([
                    ['value' => 1, 'label' => '开启'],
                    ['value' => 0, 'label' => '关闭'],
                ])->control([
                    [
                        'value' => 1,
                        'rule'=> [
                            Elm::radio('watermark_type', '水印类型')
                                ->setOptions([
                                    ['value' => 1, 'label' => '图片'],
                                    ['value' => 2, 'label' => '文字'],
                                ])
                                ->control([
                                    [
                                        'value' => 1,
                                        'rule'=> [
                                            Elm::frameImage('watermark_image', '水印图片', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=watermark_image&type=1')
                                                ->value($formData['watermark_image'] ?? '')
                                                ->modal(['modal' => false])
                                                ->width('896px')
                                                ->height('480px'),
                                            Elm::number('watermark_opacity','水印图片透明度')->required(),
                                            Elm::number('watermark_rotate','水印图片倾斜度')->required(),
                                        ]
                                    ],
                                    [
                                        'value' => 2,
                                        'rule'=> [
                                            Elm::input('watermark_text', '水印文字')->required(),
                                            Elm::number('watermark_text_size','水印文字大小（单位：px）'),
                                            Elm::color('watermark_text_color','水印字体颜色'),
                                            Elm::number('watermark_text_angle','水印字体旋转角度'),
                                        ]
                                    ],
                                ]),
                            Elm::radio('watermark_position','水印位置')->setOptions([
                                ['value' => 0, 'label' => '左上'],
                                ['value' => 1, 'label' => '中上'],
                                ['value' => 2, 'label' => '右上'],
                                ['value' => 3, 'label' => '左中'],
                                ['value' => 4, 'label' => '居中'],
                                ['value' => 5, 'label' => '中右'],
                                ['value' => 6, 'label' => '左下'],
                                ['value' => 7, 'label' => '中下'],
                                ['value' => 8, 'label' => '右下'],
                            ]),
                            Elm::number('watermark_x','水印横坐标偏移量（单位：px）'),
                            Elm::number('watermark_y','水印纵坐标偏移量（单位：px）'),
                        ]
                    ],
                ]),
        ]);
        $form->setTitle('水印配置')->formData($formData);
        return app('json')->success(formToData($form));
    }

    /**
     * 未启用
     * TODO 保存水印设置信息
     * @return \think\response\Json
     * @author Qinii
     * @day 12/14/21
     */
    public function setImageWaterConfig()
    {
        $arr = $this->request->params([
            'image_watermark_status',
            'watermark_type',
            'watermark_image',
            'watermark_opacity',
            'watermark_position',
            'watermark_rotate',
            'watermark_text',
            'watermark_text_angle',
            'watermark_text_color',
            'watermark_text_size',
            'watermark_x',
            'watermark_y'
        ]);
        app()->make(ConfigValueRepository::class)->setFormData($arr, 0);
        return app('json')->success('修改成功');
    }
}
