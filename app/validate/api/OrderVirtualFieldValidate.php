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


namespace app\validate\api;

use think\Validate;

class OrderVirtualFieldValidate extends Validate
{
    protected $failException = true;

    protected $rule = [];

    public function load(array $extend, array $value)
    {
        $extend = array_combine(array_column($extend, 'title'), $extend);
        $rule = [];
        foreach ($extend as $title => $val) {
            $item = [];
            if ($val['key'] === 'image') {
                if ($val['require']) {
                    $item[] = 'isRequireImage';
                } else {
                    $item[] = 'isImage';
                }

            } else {
                if ($val['require']) {
                    $item[] = 'require';
                }
                $item[] = 'is' . ucfirst($val['key']);
            }
            $rule[$title.' '] = implode('|', $item);
        }
        $this->rule = $rule;
        $data = [];
        foreach ($value as $v) {
            $data[(string)$v['title'].' '] = $v['value'] ?? '';
        }
        $this->check($data);
        return $data;
    }

    public function isMobile($val)
    {
        return $this->regex($val, 'mobile');
    }

    public function isDate($val)
    {
        return $this->dateFormat($val, 'Y-m-d');
    }

    public function isTime($val)
    {
        return $this->dateFormat($val, 'H:i');
    }

    public function isRequireImage($val)
    {
        if (!count($val)) return false;
        foreach ($val as $v) {
            if (!is_string($v)) return false;
        }
        return true;
    }

    public function isImage($val)
    {
        if (!count($val)) return true;
        return $this->isRequireImage($val);
    }

    public function isEmail($val)
    {
        return $this->filter($val, FILTER_VALIDATE_EMAIL);
    }

    public function isNumber($val)
    {
        return ctype_digit((string)$val);
    }

    public function isText($val)
    {
        return (bool)trim((string)$val);
    }

    public function isIdCard($val)
    {
        return $this->regex($val, 'idCard');
    }
}
