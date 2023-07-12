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


namespace app\common\repositories\system;


use app\common\dao\system\CacheDao;
use app\common\repositories\BaseRepository;
use think\db\exception\DbException;
use think\exception\ValidateException;
use think\facade\Cache;

/**
 * Class CacheRepository
 * @package app\common\repositories\system
 * @author xaboy
 * @day 2020-04-24
 * @mixin CacheDao
 */
class CacheRepository extends BaseRepository
{

    //积分说明
    const  INTEGRAL_RULE    = 'sys_integral_rule';
    //商户入驻申请协议
    const  INTEGRAL_AGREE   = 'sys_intention_agree';
    //预售协议
    const  PRESELL_AGREE    = 'sys_product_presell_agree';
    //微信菜单
    const  WECHAT_MENUS     = 'wechat_menus';
    //发票说明
    const  RECEIPT_AGREE    = 'sys_receipt_agree';
    //佣金说明
    const  EXTENSION_AGREE  = 'sys_extension_agree';
    //商户类型说明
    const  MERCHANT_TYPE    = 'sys_merchant_type';
    //分销等级规则
    const  SYS_BROKERAGE    = 'sys_brokerage';
    //用户协议
    const  USER_AGREE       = 'sys_user_agree';
    //用户隐私协议
    const  USER_PRIVACY     = 'sys_userr_privacy';
    //免费会员
    const  SYS_MEMBER       = 'sys_member';
    //关于我们
    const  ABOUT_US         = 'sys_about_us';
    //资质证照
    const  SYS_CERTIFICATE  = 'sys_certificate';
    //注销声明
    const CANCELLATION_MSG  =  'the_cancellation_msg';
    //注销重要提示
    const CANCELLATION_PROMPT = 'the_cancellation_prompt';
    //平台规则
    const PLATFORM_RULE     = 'platform_rule';
    //优惠券说明
    const COUPON_AGREE = 'sys_coupon_agree';
    //付费会员协议
    const SYS_SVIP = 'sys_svip';

    public function getAgreeList($type)
    {
        $data = [
            ['label' => '用户协议',        'key' => self::USER_AGREE],
            ['label' => '隐私政策',        'key' => self::USER_PRIVACY],
            ['label' => '平台规则',        'key' => self::PLATFORM_RULE],
            ['label' => '注销重要提示',     'key' => self::CANCELLATION_PROMPT],
            ['label' => '商户入驻申请协议', 'key' => self::INTEGRAL_AGREE],
        ];
        if (!$type) {
            $data[] = ['label' => '注销声明', 'key' => self::CANCELLATION_MSG];
            $data[] = ['label' => '关于我们', 'key' => self::ABOUT_US];
            $data[] = ['label' => '资质证照', 'key' => self::SYS_CERTIFICATE];
        }
        return $data;
    }

    public function getAgreeKey(){
        return [
            self::INTEGRAL_RULE,
            self::INTEGRAL_AGREE,
            self::PRESELL_AGREE,
            self::WECHAT_MENUS,
            self::RECEIPT_AGREE,
            self::EXTENSION_AGREE,
            self::MERCHANT_TYPE,
            self::SYS_BROKERAGE,
            self::USER_AGREE,
            self::USER_PRIVACY,
            self::SYS_MEMBER,
            self::ABOUT_US,
            self::SYS_CERTIFICATE,
            self::CANCELLATION_MSG,
            self::CANCELLATION_PROMPT,
            self::PLATFORM_RULE,
            self::COUPON_AGREE,
            self::SYS_SVIP,
        ];
    }

    /**
     * CacheRepository constructor.
     * @param CacheDao $dao
     */
    public function __construct(CacheDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param string $key
     * @param $result
     * @param int $expire_time
     * @throws DbException
     * @author xaboy
     * @day 2020-04-24
     */
    public function save(string $key, $result, int $expire_time = 0)
    {
        if (!$this->dao->fieldExists('key', $key)) {
            $this->dao->create(compact('key', 'result', 'expire_time'));
        } else {
            $this->dao->keyUpdate($key, compact('result', 'expire_time'));
        }
    }

    public function getResult($key)
    {
        $data['title'] = '';
        foreach ($this->getAgreeList(1) as $item) {
            if ($item['key'] == $key) {
                $data['title'] = $item['label'];
            }
        }
        $data[$key] = $this->dao->getResult($key) ?? '';
        return $data;
    }

    public function getResultByKey($key)
    {
        return $this->dao->getResult($key);
    }

    public function saveAll(array $data)
    {
        foreach ($data as $k => $v) {
            $this->save($k, $v);
        }
    }


    /**
     * 设置用户协议内容
     * @return mixed
     */
    public function setUserAgreement($content)
    {
        $html = <<<HTML
<!doctype html>
<html class="x-admin-sm">
    <head>
        <meta charset="UTF-8">
        <title>隐私协议</title>
        <meta name="renderer" content="webkit|ie-comp|ie-stand">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <meta http-equiv="Cache-Control" content="no-siteapp" />
    </head>
    <body class="index">
    $content
    </body>
</html>
HTML;
        file_put_contents(public_path() . 'protocol.html', $html);
    }

    public function setUserRegister($content)
    {
        $html = <<<HTML
<!doctype html>
<html class="x-admin-sm">
    <head>
        <meta charset="UTF-8">
        <title>用户协议</title>
        <meta name="renderer" content="webkit|ie-comp|ie-stand">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <meta http-equiv="Cache-Control" content="no-siteapp" />
    </head>
    <body class="index">
    $content
    </body>
</html>
HTML;
        file_put_contents(public_path() . 'register.html', $html);
    }


    /*
     * 整理城市数据用的方法
     */
    public function addres()
    {
        return [];
        $re = (Cache::get('AAAAAA'));
        unset($re['省市编码']);
        if (!$re) throw new ValidateException('无数据');
        $shen = [];
        $shi = [];
        $qu = [];
        foreach ($re as $key => $value) {
            $item = explode(',', $value);
            $cout = count($item);
            //省
            if ($cout == 2) {
                $shen[$item[1]] = [
                    'value' => $key,
                    'label' => $item[1],
                ];
            }
            //市
            if ($cout == 3) {
                if ($item[1] == '') {
                    $shen[$item[2]] = [
                        'value' => $key,
                        'label' => $item[2],
                    ];
                    $item[1] = $item[2];
                }
                $_v = [
                    'value' => $key,
                    'label' => $item[2]
                ];
                $shi[$item[1]][] = $_v;
            }
            //区
            if ($cout == 4) {
                $_v = [
                    'value' => $key,
                    'label' => $item[3]
                ];
                $qu[$item[2]][] = $_v;
            }
        }
        $data = [];
        foreach ($shen as $s => $c) {
            foreach ($shi as $i => $c_) {
                if ($c['label'] == $i) {
                    if ($c['label'] == $i) {
                        $san = [];
                        foreach ($c_ as $key => $value) {
                            if (isset($qu[$value['label']])) {
                                $value['children'] = $qu[$value['label']];
                            }
                            $san[] = $value;
                        }
                    }
                    $c['children'] = $san;
                }
            }
            $zls[$s] = $c;
        }
        $data = array_values($zls);
        file_put_contents('address.js', json_encode($data, JSON_UNESCAPED_UNICODE));
        //$this->save('applyments_addres',$data);
    }
}
