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


use app\common\model\store\order\StoreOrder;
use app\common\repositories\store\shipping\ExpressRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Config;

class ExpressService
{
    const API = 'https://wuliu.market.alicloudapi.com/kdi';

    public static function query($no, $type = '',$form = [])
    {
        $express = systemConfig('crmeb_serve_express') ?:2;

        if($express == 2){
            //一号通
            return self::serve($no, $type,$form);
        } else {
            //阿里云
            return self::ali($no, $type);
        }
    }

    /**
     * TODO 一号通查询
     * @param $no
     * @param $type
     * @return array
     * @author Qinii
     * @day 8/28/21
     */
    public static function serve($no,$type,$form)
    {
        $res = app()->make(CrmebServeServices::class)->express()->query($no,'',$form);
        if (!$res) $res = app()->make(CrmebServeServices::class)->express()->query($no,$type,$form);
        $cacheTime = 1800;
        if(!empty($res)){
            if($res['status'] == 3){
                $cacheTime = 0;
            }
        }
        $list = $res['content'] ?? [];
        return compact('cacheTime','list');
    }

    /**
     * TODO 阿里云查询
     * @param $no
     * @param $re
     * @return array|bool
     * @author Qinii
     * @day 8/28/21
     */
    public static function ali($no, $re)
    {
        //阿里云
        $appCode = systemConfig('express_app_code');
        if (!$appCode) return false;
        $type = '';

        $res = HttpService::getRequest(self::API, compact('no', 'type'), ['Authorization:APPCODE ' . $appCode]);
        if (!$res) throw new ValidateException('未查询到快递信息,请确认单号及余额是否充足');
        $result = json_decode($res, true) ?: null;

        if(!is_null($result) && $result['status'] != 200){
            if (in_array($result['status'],[201,203,204,207,205])){
                throw new ValidateException($result['msg']);
            }
        }
        $cacheTime = 1800;
        if (is_array($result) && isset($result['result']) && isset($result['result']['deliverystatus']) && $result['result']['deliverystatus'] >= 3){
            $cacheTime = 0;
        }
        $list = $result['result']['list'] ?? [];
        return compact('cacheTime','list');
    }

    /**
     * TODO
     * @param $sn    快递号
     * @param $name  快递公司
     * @param $phone 收货人手机号
     * @return array|bool|mixed
     * @author Qinii
     * @day 8/16/21
     */
    public static function express($sn,$name,$phone)
    {
        $has = Cache::has('express_' . $sn);
        if ($has) {
            $result = Cache::get('express_' . $sn);
        } else {
            $suffix = '';
            $is_shunfeng  = strtoupper(substr($sn,0,2));
            if  ($is_shunfeng ==  'SF') {
                $suffix = ':'.substr($phone,7);
            }
            $com = app()->make(ExpressRepository::class)->getSearch(['name' => $name])->value('code');
            $result = self::query($sn.$suffix, $com, ['phone' => $phone]);
            if(!empty($result['list'])){
                Cache::set('express_' . $sn, $result['list'], $result['cacheTime']);
                $result  =  $result['list'];
            }
        }
        return $result ?? [];
    }
}
