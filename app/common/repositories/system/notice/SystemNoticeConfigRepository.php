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


namespace app\common\repositories\system\notice;


use app\common\dao\system\notice\SystemNoticeConfigDao;
use app\common\repositories\BaseRepository;
use crmeb\exceptions\WechatException;
use crmeb\services\MiniProgramService;
use crmeb\services\WechatService;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Route;

/**
 * Class SystemNoticeConfigRepository
 * @package app\common\repositories\system\notice
 * @author xaboy
 * @day 2020/11/6
 * @mixin SystemNoticeConfigDao
 */
class SystemNoticeConfigRepository extends BaseRepository
{
    public function __construct(SystemNoticeConfigDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->getSearch($where);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('create_time ASC')->select();
        return compact('count', 'list');
    }

    public function form(?int $id)
    {
        $formData = [];
        if ($id) {
            $data = $this->dao->get($id);
            if (!$data) throw new ValidateException('数据不存在');
             $formData  = $data->toArray();
            $form = Elm::createForm(Route::buildUrl('systemNoticeConfigUpdate', ['id' => $id])->build());
        }  else {
            $form = Elm::createForm(Route::buildUrl('systemNoticeConfigCreate')->build());
        }

        $form->setRule([
            Elm::input('notice_title', '通知名称')->required(),
            Elm::input('notice_info', '通知说明')->required(),
            Elm::input('notice_key', '通知KEY')->required(),
            Elm::radio('notice_sys', '站内消息', -1)->options([
                ['value' => 0, 'label' => '关闭'],
                ['value' => 1, 'label' => '开启'],
                ['value' => -1, 'label' => '无'],
            ])->requiredNum(),
            Elm::radio('notice_wechat', '公众号模板消息', -1)->options([
                ['value' => 0, 'label' => '关闭'],
                ['value' => 1, 'label' => '开启'],
                ['value' => -1, 'label' => '无'],
            ])->requiredNum(),
            Elm::radio('notice_routine', '小程序订阅消息', -1)->options([
                ['value' => 0, 'label' => '关闭'],
                ['value' => 1, 'label' => '开启'],
                ['value' => -1, 'label' => '无'],
            ])->requiredNum(),
            Elm::radio('notice_sms', '短信消息', -1)->options([
                ['value' => 0, 'label' => '关闭'],
                ['value' => 1, 'label' => '开启'],
                ['value' => -1, 'label' => '无'],
            ])->requiredNum(),
            Elm::radio('type', '通知类型', 0)->options([
                ['value' => 0, 'label' => '用户'],
                ['value' => 1, 'label' => '商户'],
            ])->requiredNum(),
            Elm::textarea('sms_content','短信内容'),
            Elm::textarea('wechat_content','公众号模板内容'),
            Elm::textarea('routine_content','小程序订阅消息内容'),
        ]);

        return $form->setTitle(is_null($id) ? '添加通知' : '编辑通知')->formData($formData);
    }

    public function swithStatus($id, $filed, $status)
    {
        $data = $this->dao->get($id);
        if ($data[$filed] == -1) throw  new ValidateException('该消息无此通知类型');
        $data->$filed = $status;
        $data->save();
    }

    /**
     * TODO
     * @param $key
     * @return bool
     * @author Qinii
     * @day 11/19/21
     */
    public function getNoticeSys($key)
    {
        return $this->dao->getNoticeStatusByKey($key, 'notice_sys');
    }

    /**
     * TODO
     * @param $key
     * @return bool
     * @author Qinii
     * @day 11/19/21
     */
    public function getNoticeSms($key)
    {
        return $this->dao->getNoticeStatusByKey($key, 'notice_sms');
    }

    /**
     * TODO
     * @param $key
     * @return bool
     * @author Qinii
     * @day 11/19/21
     */
    public function getNoticeWechat($key)
    {
        return $this->dao->getNoticeStatusByKey($key, 'notice_wechat');
    }

    /**
     * TODO
     * @param $key
     * @return bool
     * @author Qinii
     * @day 11/19/21
     */
    public function getNoticeRoutine($key)
    {
        return $this->dao->getNoticeStatusByKey($key, 'notice_routine');
    }

    public function getSmsTemplate(string $key)
    {
        $temp = $this->dao->getWhere(['const_key' => $key]);

        if ($temp && $temp['notice_sms'] == 1) {
            return systemConfig('sms_use_type') == 2 ? $temp['sms_ali_tempid'] : $temp['sms_tempid'];
        }
        return '';
    }

    /**
     * TODO 编辑消息模板ID
     * @param $id
     * @return \FormBuilder\Form
     * @author Qinii
     * @day 6/9/22
     */
    public function changeForm($id)
    {
        $formData = $this->dao->get($id);
        if (!$formData) throw new ValidateException('数据不存在');
        $form = Elm::createForm(Route::buildUrl('systemNoticeConfigSetChangeTempId', ['id' => $id])->build());
        $children = [];
        $value = '';
        if ($formData->notice_sms != -1) {
            $value = 'sms';
            if (systemConfig('sms_use_type') == 2) {
                $sms = [
                    'type' => 'el-tab-pane',
                    'props' => [
                        'label' => '阿里云短信',
                        'name' => 'sms'
                    ],
                    'children' =>[
                        Elm::input('title','通知类型', $formData->notice_title)->disabled(true),
                        Elm::input('info','场景说明', $formData->notice_info)->disabled(true),
                        Elm::input('sms_ali_tempid','短信模板ID'),
                        Elm::input('notice_info','短信说明')->disabled(true),
                        Elm::textarea('sms_content','短信内容')->disabled(true),
                        Elm::switches('notice_sms', '是否开启', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
                    ]
                ];
            } else {
                $sms = [
                    'type' => 'el-tab-pane',
                    'props' => [
                        'label' => '一号通短信',
                        'name' => 'sms'
                    ],
                    'children' =>[
                        Elm::input('title','通知类型', $formData->notice_title)->disabled(true),
                        Elm::input('info','场景说明', $formData->notice_info)->disabled(true),
                        Elm::input('sms_tempid','短信模板ID'),
                        Elm::input('notice_info','短信说明')->disabled(true),
                        Elm::textarea('sms_content','短信内容')->disabled(true),
                        Elm::switches('notice_sms', '是否开启', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
                    ]
                ];
            }
            $children[] = $sms;
        }
        if ($formData->notice_wechat != -1 &&  $formData->wechatTemplate ) {
            if (!$value)  $value = 'wechat';
            $children[] = [
                'type' => 'el-tab-pane',
                'props' => [
                    'label' => '模板消息',
                    'name' => 'wechat'
                ],
                'children' =>[
                    Elm::input('title1','通知类型', $formData->wechatTemplate->name)->disabled(true),
                    Elm::input('info1','场景说明', $formData->notice_info)->disabled(true),
                    Elm::input('wechat_tempkey','模板消息编号', $formData->wechatTemplate->tempkey)->disabled(true),
                    Elm::input('wechat_tempid','模板消息ID', $formData->wechatTemplate->tempid),
                    Elm::textarea('wechat_content','模板消息内容', $formData->wechatTemplate->content)->disabled(true),
                    Elm::switches('notice_wechat', '是否开启', 1)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
                ]
            ];
        }
        if ($formData->notice_routine != -1 && $formData->routineTemplate) {
            if (!$value)  $value = 'routine';
            $children[] = [
                'type' => 'el-tab-pane',
                'props' => [
                    'label' => '订阅消息',
                    'name' => 'routine'
                ],
                'children' =>[
                    Elm::input('title2','通知类型', $formData->routineTemplate->name)->disabled(true),
                    Elm::input('info2','场景说明', $formData->notice_info)->disabled(true),
                    Elm::input('routine_tempkey','订阅消息编号', $formData->routineTemplate->tempkey)->disabled(true),
                    Elm::input('routine_tempid','订阅消息ID', $formData->routineTemplate->tempid),
                    Elm::textarea('routine_content','订阅消息内容', $formData->routineTemplate->content)->disabled(true),
                    Elm::switches('notice_routine', '是否开启', $formData->notice_routine)->activeValue(1)->inactiveValue(0)->inactiveText('关')->activeText('开'),
                ]
            ];
        }
        $form->setRule([
            [
                'type' => 'el-tabs',
                'native' => true,
                'props' => [
                    'value' => $value
                ],
                'children' => $children
            ]
        ]);
        return $form->setTitle( '编辑消息模板')->formData($formData->toArray());
    }

    public function save($id, $data)
    {
        $result = $this->dao->get($id);
        if (isset($data['routine_tempid'])) {
            $result->routineTemplate->tempid = $data['routine_tempid'];
            $result->routineTemplate->save();
            unset($data['routine_tempid']);
        }
        if (isset($data['wechat_tempid'])) {
            $result->wechatTemplate->tempid = $data['wechat_tempid'];
            $result->wechatTemplate->save();
            unset($data['wechat_tempid']);
        }
        if (!empty($data)) $this->dao->update($id,$data);

    }
}
