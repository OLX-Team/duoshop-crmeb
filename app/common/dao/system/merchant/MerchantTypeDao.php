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


namespace app\common\dao\system\merchant;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\merchant\MerchantType;

class MerchantTypeDao extends BaseDao
{

    protected function getModel(): string
    {
        return MerchantType::class;
    }

    public function search(array $where = [])
    {
        return MerchantType::getDB()
            ->when(isset($where['mer_type_id']) && $where['mer_type_id'] !== '',function($query) use($where){
                $query->where('mer_type_id',$where['mer_type_id']);
            });
    }

    public function getOptions()
    {
        $data = MerchantType::getDB()->column('type_name', 'mer_type_id');
        $options = [];
        foreach ($data as $value => $label) {
            $options[] = compact('value', 'label');
        }
        return $options;
    }

    public function getMargin()
    {
        $data = MerchantType::getDB()->column('margin,is_margin', 'mer_type_id');
        $options = [];
        foreach ($data as $value => $item) {
            if ($item['is_margin'] == 1) {
                $options[] = [
                    'value' => $value,
                    'rule' => [
                        [
                            'type' => 'div',
                            'children' => [
                                '保证金：' . $item['margin']. ' 元'
                            ],
                            'style' => [
                                'paddingTop' => '100px',
                            ],
                        ]
                    ]
                ];
            }
        }
        return $options;
    }


}
