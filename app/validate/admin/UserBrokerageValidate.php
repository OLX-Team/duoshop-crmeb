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


namespace app\validate\admin;


use app\common\repositories\user\UserBrokerageRepository;
use think\Validate;

class UserBrokerageValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'brokerage_level|会员等级' => 'require|integer|>:0',
        'brokerage_name|会员名称' => 'require|max:16',
        'brokerage_icon|会员图标' => 'require',
        'brokerage_rule|会员升级规则' => 'requireIf:type,0|array|checkBrokerageRule',
        'extension_one|一级佣金比例' => 'requireIf:type,0|float|>=:0|<=:100',
        'extension_two|二级佣金比例' => 'requireIf:type,0|float|>=:0|<=:100',
        'image|背景图' => 'requireIf:type,1|max:128',
        'value|会员成长值' => 'requireIf:type,1|float|>=:0',
        'type|类型' => 'require|in:0,1',
    ];

    public function checkBrokerageRule($value, $rlue, $data)
    {
        if (!$data['type']) {
            $types = UserBrokerageRepository::BROKERAGE_RULE_TYPE;
            if (count($types) != count($value)) {
                return '请输入正确的升级任务';
            }
            $flag = 0;
            foreach ($types as $type) {
                $val = $value[$type] ?? '';
                if (!is_array($val) || !isset($val['name'], $val['num'], $val['info']) || count($val) != 3) return '请输入正确的升级任务';
                if ($val['num'] < 0)
                    return '请输入正确的任务数量';
                if ($val['num'] > 0 && !$val['name']) return '请输入任务名称';
                if ($val['num'] > 0) $flag++;
            }
            if (!$flag) return '请至少设置一个升级任务';
        }

        return true;
    }
}
