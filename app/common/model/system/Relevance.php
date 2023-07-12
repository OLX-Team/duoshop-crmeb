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


namespace app\common\model\system;

use app\common\model\store\product\Spu;
use app\common\model\BaseModel;
use app\common\model\community\Community;
use app\common\model\store\StoreCategory;
use app\common\model\system\auth\Menu;
use app\common\model\system\merchant\Merchant;
use app\common\model\user\User;
use app\common\repositories\system\RelevanceRepository;

class Relevance extends BaseModel
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tablePk(): string
    {
        return 'relevance_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tableName(): string
    {
        return 'relevance';
    }

    public function fans()
    {
        return $this->hasOne(User::class,'uid','left_id');
    }

    public function focus()
    {
        return $this->hasOne(User::class,'uid','right_id');
    }

    public function community()
    {
        return $this->hasOne(Community::class,'community_id','right_id')
            ->bind(['community_id','title','image','start','uid','create_time','count_start','author','is_type']);
    }

    public function getIsStartAttr()
    {
        return self::where('left_id', $this->right_id)
            ->where('right_id',$this->left_id)
            ->where('type',RelevanceRepository::TYPE_COMMUNITY_FANS)
            ->count() > 0;
    }

    public function spu()
    {
        return $this->hasOne(Spu::class, 'spu_id','right_id');
    }
    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id','right_id');
    }

    public function category()
    {
        return $this->hasOne(StoreCategory::class, 'store_category_id','right_id');
    }


    public function auth()
    {
        return $this->hasOne(Menu::class, 'menu_id','right_id');
    }

    public function searchLeftIdAttr($query, $value)
    {
        $query->where('left_id', $value);
    }

    public function searchRightIdAttr($query, $value)
    {
        $query->where('right_id', $value);
    }

    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }

}
