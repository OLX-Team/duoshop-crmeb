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


namespace app\validate\merchant;


use think\Validate;

class BroadcastGoodsValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'name|商品名称' => 'require|min:3|max:14',
        'cover_img|商品图' => 'require',
        'price|价格' => 'require|min:0.01',
        'product_id|商品' => 'require|array|length:2',
    ];

    public function isBatch()
    {
        $this->rule['product_id|商品'] = 'require|integer';
        return $this;
    }
}
