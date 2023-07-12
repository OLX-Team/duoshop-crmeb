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

namespace app\common\dao\store\product;

use app\common\dao\BaseDao;
use app\common\model\store\product\ProductAssistSet;
use app\common\model\system\merchant\Merchant;
use app\common\repositories\system\merchant\MerchantRepository;
use think\Exception;

class ProductAssistSetDao extends BaseDao
{
    protected function getModel(): string
    {
        return ProductAssistSet::class;
    }


    public function incNum(int $type,int $id,int $inc = 1)
    {
        try{
            $query = $this->getModel()::where($this->getPk(),$id);
            if($type == 1) $query->inc('share_num',$inc)->update();
            if($type == 2) $query->inc('view_num',$inc)->update();
        }catch (Exception $exception){

        }
    }

    public function userCount()
    {
        $count = $this->getModel()::getDB()->count("*");
        $res = $this->getModel()::getDB()->order('create_time DESC')->with(['user' => function($query){
            $query->field('uid,avatar avatar_img');
        }])->limit(10)->group('uid')->select()->toArray();

        $list = [];
        foreach ($res as $item){
            if(isset($item['user']['avatar_img']) && $item['user']['avatar_img']){
                $list[] = $item['user'];
            }
        }
        return compact('count','list');
    }

    /**
     * TODO 更新状态
     * @param int $id
     * @author Qinii
     * @day 2020-11-25
     */
    public function changStatus(int $id)
    {
        $this->getModel()::getDB()->where($this->getPk(),$id)->update(['status' => 20]);
    }
}

