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

use app\common\repositories\store\product\ProductAssistSetRepository;
use app\common\repositories\store\product\ProductGroupBuyingRepository;
use app\common\repositories\store\product\ProductGroupRepository;
use app\common\repositories\store\product\ProductPresellRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\SpuRepository;
use think\exception\ValidateException;
use think\helper\Str;

class CopyCommand
{
    protected $type = [
        '0' => 'p/:', //普通商品
        '1' => 's/:', //秒杀商品
        '2' => 'r/:', //预售商品
        '4' => 'g/:', //拼团商品
        '30' => 'sa/:', //助力活动
        '40' => 'gb/:', //拼团活动
    ];
    protected $productType = [
        'p'  => 0,
        's'  => 1,
        'r'  => 2,
        'g'  => 4,
        'sa' => 30,
        'gb' => 40,
    ];

    /**
     * TODO 创建口令
     * @param int $id
     * @param string $type
     * @param $userInfo
     * @return string
     * @author Qinii
     * @day 9/8/21
     */
    public function create(int $id, string $type, $userInfo)
    {
        $data = $this->getTitle($id, $type);
        $str = $this->setNumberToStr($data['id'], $type);
        if ($userInfo->uid) $str .= '嗯'.dechex($userInfo->uid??0);

        return $str. '*/  '.$data['title'];
    }

    /**
     * TODO 创建口令ID
     * @param $id
     * @param $type
     * @return string
     * @author Qinii
     * @day 9/8/21
     */
    public function setNumberToStr($id, $type)
    {
        $count = 10;
        $id = dechex($id);
        $strlen = strlen($id);
        $str = '/@'. $strlen.$this->type[$type];
        $str .= Str::random(($count-$strlen),null);
        $str .= $id;
        return $str;
    }

    /**
     * TODO 商品信息
     * @param $id
     * @param $type
     * @return array
     * @author Qinii
     * @day 9/8/21
     */
    public function getTitle($id, $type)
    {
        switch ($type){
            case 0:
                $ret = app()->make(ProductRepository::class)->get($id);
                $title = $ret->store_name;
                break;
            case 1:
                $ret = app()->make(ProductRepository::class)->get($id);
                $title = $ret->store_name;
                break;
            case 2:
                $ret = app()->make(ProductPresellRepository::class)->get($id);
                $title = $ret->store_name;
                break;
            case 4:
                $ret = app()->make(ProductGroupRepository::class)->get($id);
                $title = $ret->product->store_name;
                break;
            case 30:
                $ret = app()->make(ProductAssistSetRepository::class)->get($id);
                if (!$ret) throw new ValidateException('数据不存在');
                $title = $ret->assist->store_name;
                break;
            case 40:
                $ret = app()->make(ProductGroupBuyingRepository::class)->get($id);
                if (!$ret) throw new ValidateException('数据不存在');
                $title = $ret->productGroup->product->store_name;
                break;
            default:
                return ;
                break;
        }
        return compact('title','id');
    }

    /**
     * TODO 解析口令
     * @param string $key
     * @return array
     * @author Qinii
     * @day 9/8/21
     */
    public function getMassage(string $key)
    {
        $key = rtrim(ltrim($key));
        try{
            $com = explode('*/',$key)[0].'*/';
            $key = str_replace('/@','',$key);
            $keyArray = explode('/:',$key);
            $num = substr($keyArray[0],0,1);
            $idArray = explode('嗯',$keyArray[1]);
            $id = substr($idArray[0],-$num);
            $id = hexdec($id);
            $type_ = substr($keyArray[0],1);
            $uidArray = explode('*/',$idArray[1]);
            $uid = hexdec($uidArray[0]);
            $type = $this->productType[$type_];
            return compact('type','id','uid','com');
        } catch (\Exception $exception){
            return [];
        }
    }
}
