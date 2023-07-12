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


use Lizhichao\Word\VicWord;

class VicWordService
{

    public function getWord($str)
    {
        $data = [$str];
        if (systemConfig('vic_word_status')) {
            ini_set('memory_limit', '-1');
            $vicWord = new VicWord();
            $word = $vicWord->getAutoWord($str);
            foreach ($word as $item) {
                if ($item[2] === '0' || is_numeric($item[0])) {
                    continue;
                }
                $data[] = $item[0];
            }
        }
        return $data;
    }
}
