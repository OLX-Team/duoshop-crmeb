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


namespace app\common\dao\store;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\store\CityArea;

class CityAreaDao extends BaseDao
{

    protected function getModel(): string
    {
        return CityArea::class;
    }

    public function search(array $where)
    {
        return CityArea::getDB()->when(isset($where['pid']) && $where['pid'] !== '', function ($query) use ($where) {
            $query->where('parent_id', $where['pid']);
        })->when(isset($where['address']) && $where['address'] !== '', function ($query) use ($where) {
            $address = explode('/', trim($where['address'], '/'));
            $p = array_shift($address);
            if (mb_strlen($p) - 1 === mb_strpos($p, '市')) {
                $p = mb_substr($p, 0, -1);
            } elseif (mb_strlen($p) - 1 === mb_strpos($p, '省')) {
                $p = mb_substr($p, 0, -1);
            } elseif (mb_strlen($p) - 3 === mb_strpos($p, '自治区')) {
                $p = mb_substr($p, 0, -3);
            }
            $pcity = $this->search([])->where('name', $p)->find();
            $street = array_pop($address);
            if ($pcity) {
                $path = '/' . $pcity->id . '/';
                $query->whereLike('path', "/{$pcity->id}/%");
                foreach ($address as $item) {
                    $id = $this->search([])->whereLike('path', $path . '%')->where('name', $item)->value('id');
                    if ($id) {
                        $path .= $id . '/';
                    } else {
                        break;
                    }
                }
            }
            $query->whereLike('path', $path . '%')->where('name', $street);
        });
    }

    public function getCityList(CityArea $city)
    {
        if (!$city->parent_id) return [$city];
        $lst = $this->search([])->where('id', 'in', explode('/', trim($city->path, '/')))->order('id ASC')->select();
        $lst[] = $city;
        return $lst;
    }
}
