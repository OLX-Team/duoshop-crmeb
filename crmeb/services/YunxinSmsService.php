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


namespace crmeb\services;


use app\common\dao\system\sms\SmsRecordDao;
use app\common\repositories\store\broadcast\BroadcastRoomRepository;
use app\common\repositories\store\order\StoreGroupOrderRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\order\StoreRefundOrderRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\ProductTakeRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\notice\SystemNoticeConfigRepository;
use crmeb\exceptions\SmsException;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Route;

/**
 * Class YunxinSmsService
 * @package crmeb\services
 * @author xaboy
 * @day 2020-05-18
 */
class YunxinSmsService
{
    /**
     * api
     */
    const API = 'https://sms.crmeb.net/api/';
    // const API = 'http://plat.crmeb.net/api/';

    /**
     * @var array
     */
    protected $config;

    /**
     * YunxinSmsService constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        if (isset($this->config['sms_token'])) {
            $this->config['sms_token'] = $this->getToken();
        }
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-05-18
     */
    protected function getToken()
    {
        return md5($this->config['sms_account'] . $this->config['sms_token']);
    }

    /**
     * @author xaboy
     * @day 2020-05-18
     */
    public function checkConfig()
    {
        if (!isset($this->config['sms_account']) || !$this->config['sms_account']) {
            throw new ValidateException('请登录短信账户');
        }
        if (!isset($this->config['sms_token']) || !$this->config['sms_token']) {
            throw new ValidateException('请登录短信账户');
        }
    }

    /**
     * 发送注册验证码
     * @param $phone
     * @return mixed
     */
    public function captcha($phone)
    {
        return json_decode(HttpService::getRequest(self::API . 'sms/captcha', compact('phone')), true);
    }

    /**
     * 短信注册
     * @param $account
     * @param $password
     * @param $url
     * @param $phone
     * @param $code
     * @param $sign
     * @return mixed
     */
    public function register($account, $password, $url, $phone, $code, $sign)
    {
        return $this->registerData(compact('account', 'password', 'url', 'phone', 'code', 'sign'));
    }

    /**
     * @param array $data
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function registerData(array $data)
    {
        return json_decode(HttpService::postRequest(self::API . 'sms/register', $data), true);
    }

    /**
     * 公共短信模板列表
     * @param array $data
     * @return mixed
     */
    public function publictemp(array $data = [])
    {
        $this->checkConfig();
        $data['account'] = $this->config['sms_account'];
        $data['token'] = $this->config['sms_token'];
        $data['source'] = 'crmeb_merchant';
        return json_decode(HttpService::postRequest(self::API . 'sms/publictemp', $data), true);
    }

    /**
     * 公共短信模板添加
     * @param $id
     * @param $tempId
     * @return mixed
     */
    public function use($id, $tempId)
    {
        $this->checkConfig();
        $data = [
            'account' => $this->config['sms_account'],
            'token' => $this->config['sms_token'],
            'id' => $id,
            'tempId' => $tempId,
        ];

        return json_decode(HttpService::postRequest(self::API . 'sms/use', $data), true);
    }

    /**
     * @param string $templateId
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function getTemplateCode(string $templateId)
    {
        return Config::get('sms.template_id.' . $templateId);
    }

    /**
     *  原 send 方法 （弃用）
     * 发送短信
     * @param string $phone
     * @param string $templateId
     * @param array $data
     * @return bool|string
     * @throws SmsException
     */
    public function sendDe(string $phone, string $templateId, array $data = [])
    {
        if (!$phone) {
            throw new SmsException('Mobile number cannot be empty');
        }

        $this->checkConfig();

        $formData['uid'] = $this->config['sms_account'];
        $formData['token'] = $this->config['sms_token'];
        $formData['mobile'] = $phone;
        $formData['template'] = $this->getTemplateCode($templateId);
        if (is_null($formData['template']))
            throw new SmsException('Missing template number');

        $formData['param'] = json_encode($data);
        $resource = json_decode(HttpService::postRequest(self::API . 'sms/send', $formData), true);
        if ($resource['status'] === 400) {
            throw new SmsException($resource['msg']);
        } else {
            app()->make(SmsRecordDao::class)->create([
                'uid' => $formData['uid'],
                'phone' => $phone,
                'content' => $resource['data']['content'],
                'template' => $resource['data']['template'],
                'record_id' => $resource['data']['id']
            ]);
        }
        return $resource;
    }

    /**
     * 账号信息
     * @return mixed
     */
    public function count()
    {
        $this->checkConfig();
        return json_decode(HttpService::postRequest(self::API . 'sms/userinfo', [
            'account' => $this->config['sms_account'],
            'token' => $this->config['sms_token']
        ]), true);
    }

    /**
     * 支付套餐
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function meal($page, $limit)
    {
        return json_decode(HttpService::getRequest(self::API . 'sms/meal', [
            'page' => $page,
            'limit' => $limit
        ]), true);
    }

    /**
     * 支付码
     * @param $payType
     * @param $mealId
     * @param $price
     * @param $attach
     * @param $notify
     * @return mixed
     */
    public function pay($payType, $mealId, $price, $attach, $notify = null)
    {
        $this->checkConfig();
        $data['uid'] = $this->config['sms_account'];
        $data['token'] = $this->config['sms_token'];
        $data['payType'] = $payType;
        $data['mealId'] = $mealId;
        $data['notify'] = $notify ?? Route::buildUrl('SmsNotify')->build();
        $data['price'] = $price;
        $data['attach'] = $attach;
        return json_decode(HttpService::postRequest(self::API . 'sms/mealpay', $data), true);
    }

    /**
     * 申请模板消息
     * @param $title
     * @param $content
     * @param $type
     * @return mixed
     */
    public function apply($title, $content, $type)
    {
        $this->checkConfig();
        $data['account'] = $this->config['sms_account'];
        $data['token'] = $this->config['sms_token'];
        $data['title'] = $title;
        $data['content'] = $content;
        $data['type'] = $type;
        return json_decode(HttpService::postRequest(self::API . 'sms/apply', $data), true);
    }

    /**
     * 短信模板列表
     * @param $data
     * @return mixed
     */
    public function template(array $data)
    {
        $this->checkConfig();
        return json_decode(HttpService::postRequest(self::API . 'sms/template', $data + [
            'account' => $this->config['sms_account'], 'token' => $this->config['sms_token']
        ]), true);
    }

    /**
     * 获取短息记录状态
     * @param $record_id
     * @return mixed
     */
    public function getStatus(array $record_id)
    {
        return json_decode(HttpService::postRequest(self::API . 'sms/status', [
            'record_id' => json_encode($record_id)
        ]), true);
    }

    /**
     * @return YunxinSmsService
     * @author xaboy
     * @day 2020-05-18
     */
    public static function create()
    {
        /** @var ConfigValueRepository $make */
        $make = app()->make(ConfigValueRepository::class);
        $config = $make->more(['sms_account', 'sms_token'], 0);

        return new static($config);
    }

    /**
     * @param string $sms_account
     * @param string $sms_token
     * @return $this
     * @author xaboy
     * @day 2020-05-18
     */
    public function setConfig(string $sms_account, string $sms_token)
    {
        $this->config = compact('sms_token', 'sms_account');
        $this->config['sms_token'] = $this->getToken();
        return $this;
    }

    /**
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-18
     */
    public function form()
    {
        return Elm::createForm(Route::buildUrl('smsCreate')->build(), [
            Elm::input('title', '模板名称'),
            Elm::input('content', '模板内容')->type('textarea'),
            Elm::radio('type', '模板类型', 1)->options([['label' => '验证码', 'value' => 1], ['label' => '通知', 'value' => 2], ['label' => '推广', 'value' => 3]])
        ])->setTitle('申请短信模板');
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020-05-18
     */
    public function account()
    {
        $this->checkConfig();
        return $this->config['sms_account'];
    }

    /**
     * @Author:Qinii
     * @Date: 2020/9/19
     * @param $data
     * @return mixed
     */
    public function smsChange($data)
    {
        $this->checkConfig();
        $data['account'] = $this->config['sms_account'];
        $data['token'] = $this->config['sms_token'];
        return json_decode(HttpService::postRequest(self::API . 'sms/modify', $data), true);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/9/19
     * @param $phone
     * @param $code
     * @param $type
     * @return bool
     */
    public function checkSmsCode($phone, $code, $type)
    {
        if (!env('DEVELOPMENT',false)) {
            $sms_key = $this->sendSmsKey($phone, $type);
            if (!$cache_code = Cache::get($sms_key)) return false;
            if ($code != $cache_code) return false;
            Cache::delete($sms_key);
        }
        return true;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/9/19
     * @param $phone
     * @param string $type
     * @return string
     */
    public function sendSmsKey($phone, $type = 'login')
    {
        switch ($type) {
            case 'login': //登录
                return 'api_login_' . $phone;
                break;
            case 'binding': //绑定手机号
                return 'api_binding_' . $phone;
                break;
            case 'intention': //申请入住
                return 'merchant_intention_' . $phone;
                break;
            case 'change_pwd': //修改密码
                return 'change_pwd_' . $phone;
                break;
            case 'change_phone': //修改手机号
                return 'change_phone_' . $phone;
                break;
            default:
                return 'crmeb_' . $phone;
                break;
        }
    }

    public function send(string $phone, string $templateId, array $data = [])
    {
        try {
            $make = app()->make(CrmebServeServices::class)->sms();
            $resource = $make->send($phone,  $this->getTemplateCode($templateId), $data);
            if ($resource) {
                app()->make(SmsRecordDao::class)->create([
                    'uid' => $this->config['sms_account'],
                    'phone' => $phone,
                    'content' => $resource['content'],
                    'template' => $resource['template'],
                    'record_id' => $resource['id'],
                ]);
            }
        } catch (\Exception $exception) {
            throw new SmsException($exception->getMessage());
        }
    }
}
