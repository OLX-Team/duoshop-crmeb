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


namespace app\common\model;


use think\db\BaseQuery;
use think\Model;

/**
 * Class BaseModel
 * @package app\common\model
 * @author xaboy
 * @day 2020-03-30
 */
abstract class BaseModel extends Model
{
    protected $updateTime = false;

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    abstract public static function tablePk():? string;

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    abstract public static function tableName(): string;

    /**
     * BaseModel constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->pk = static::tablePk();
        $this->name = static::tableName();
        parent::__construct($data);
    }

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        return new static();
    }

    /**
     * @param array $scope
     * @return BaseQuery
     * @author xaboy
     * @day 2020-03-30
     */
    public static function getDB(array $scope = [])
    {
        return self::getInstance()->db($scope);
    }

}
