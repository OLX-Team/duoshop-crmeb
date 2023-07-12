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


namespace app\common\repositories\article;


use app\common\dao\article\ArticleContentDao;
use app\common\repositories\BaseRepository;

class ArticleContentRepository extends BaseRepository
{
    public function __construct(ArticleContentDao $dao)
    {
        $this->dao = $dao;
    }
}
