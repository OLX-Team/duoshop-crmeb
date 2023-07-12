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

class MerchantApplymentsValidate extends Validate
{
    protected $failException = true;

    //2401：小微商户，指无营业执照的个人商家。
    //2500：个人卖家，指无营业执照，已持续从事电子商务经营活动满6个月，且期间经营收入累计超过20万元的个人商家。（若选择该主体，请在“补充说明”填写相关描述）
    //4：个体工商户，营业执照上的主体类型一般为个体户、个体工商户、个体经营。
    //2：企业，营业执照上的主体类型一般为有限公司、有限责任公司。
    //3：党政、机关及事业单位，包括国内各级、各类政府机构、事业单位等（如：公安、党 团、司法、交通、旅游、工商税务、市政、医疗、教育、学校等机构）。
    //1708：其他组织，不属于企业、政府/事业单位的组织机构（如社会团体、民办非企业、基 金会），要求机构已办理组织机构代码证。

    protected $rule = [
        'organization_type|主体类型' => 'require|in:2,3,4,2401,2500,1708',
        'business_license_info|营业执照/登记证书信息' => 'checkBusinessInfo',
//        'organization_cert_info|组织机构代码证信息' => 'checkOrganization',
        'id_doc_type|证件类型' => 'require|in:1,2,3,4,5,6,7,8',
        'id_card_info|经营者/法人身份证信息' => 'checkIdCardInfo',
        'id_doc_info|经营者/法人身份证信息' => 'checkIdDocInfo',
//        'need_account_info|是否填写结算银行账户' => 'require|in:true,false',  废弃字段
        'account_info|结算银行账户'  => 'getAccountInfo',
        'contact_info|超级管理员信息'  => 'getContactInfo',
        'sales_scene_info|店铺信息'=>'checkSalesSceneInfo',
        'merchant_shortname|商户简称' => 'require',
        'business_addition_desc' => 'checkBusinessAdditionDesc',
    ];

    /**
     * TODO 营业执照/登记证书信息
     * @param $item
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Qinii
     * @day 6/22/21
     */
    protected function checkBusinessInfo($item,$rule,$data)
    {
        if(!in_array($data['organization_type'],['2401','2500'])){
            if(empty($item)) return '营业执照/登记证书信息为空';

            if(!isset($item['business_license_copy']) || empty($item['business_license_copy'])) return '证件扫描件为空';
            if(!isset($item['business_license_number']) || empty($item['business_license_number'])) return '证件注册号为空';
            if(!isset($item['merchant_name']) || empty($item['merchant_name'])) return '商户名称为空';
            if(!isset($item['legal_person']) || empty($item['legal_person'])) return '经营者/法定代表人姓名为空';

            if(isset($item['business_time'])) {
                $statr = $item['business_time'][0];
                $end = $item['business_time'][1];
                if ($end !== '长期') {
                    $statr = strtotime($statr);
                    $end = strtotime($end);
                    $t = $end - $statr;
                    if (($t / (3600 * 24)) <= 60) return '营业执照/登记证书有效期必须大于60天，即结束时间距当前时间需超过60天';
                }
            }

        }
       return true;
    }

    /**
     * TODO 组织机构代码证信息
     * @param $item
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Qinii
     * @day 6/22/21
     */
    protected function checkOrganization($item,$rule,$data)
    {
        $len = strlen($data['business_license_info']['business_license_number']);
        if(!in_array($data['organization_type'],['4','2401','2500']) && $len === 18){
            if(empty($item)) return '组织机构代码证信息为空';

            if(!isset($item['organization_copy']) || empty($item['organization_copy'])) return '组织机构代码证照片为空';
            if(!isset($item['organization_number']) || empty($item['organization_number'])) return '组织机构代码为空';
            if(!isset($item['organization_time']) || empty($item['organization_time'])) return '组织机构代码有效期限为空';

//            list($statr,$end) = explode(',',$item['organization_time']);

            $statr = $item['organization_time'][0];
            $end = $item['organization_time'][1];

            if($end !== '长期') {
                $statr = strtotime($statr);
                $end = strtotime($end);
                $t = $end - $statr;
                if(($t/(3600 * 24)) <= 60) return '组织机构代码证有效期必须大于60天，即结束时间距当前时间需超过60天';
            }
        }
        return true;
    }

    /**
     * TODO 经营者/法人身份证信息/身份证
     * @param $item
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Qinii
     * @day 6/22/21
     */
    protected function checkIdCardInfo($item,$rule,$data)
    {
        if($data['id_doc_type'] == 1){
            if(empty($item)) return '经营者/法人身份证信息为空';

            if(!isset($item['id_card_copy']) || empty($item['id_card_copy'])) return '身份证人像面照片为空';
            if(!isset($item['id_card_national']) || empty($item['id_card_national'])) return '身份证国徽面照片为空';
            if(!isset($item['id_card_name']) || empty($item['id_card_name'])) return '身份证姓名为空';
            if(!isset($item['id_card_number']) || empty($item['id_card_number'])) return '身份证号码为空';
            if(!isset($item['id_card_valid_time_begin']) || empty($item['id_card_valid_time_begin'])) return '经营者/法人身份证信息身份证开始时间为空';
            if(!isset($item['id_card_valid_time']) || empty($item['id_card_valid_time'])) return '经营者/法人身份证信息身份证有效期限为空';

            if($item['id_card_valid_time'] !== '长期') {
                $statr = time();
                $end = strtotime($item['id_card_valid_time']);
                $t = $end - $statr;
                if(($t/(3600 * 24)) <= 60) return '经营者/法人身份证信息证件结束日期必须大于60天，即结束时间距当前时间需超过60天';
                if(strtotime($item['id_card_valid_time_begin']) >= strtotime($item['id_card_valid_time'])) return '经营者/法人身份证信息证件结束日期必须大于证件开始时间';
            }
            if($data['organization_type']  === 2){
                if(!isset($item['id_card_address']) || empty($item['id_card_address'])) return '经营者/法人身份证信息身份证居住地址为空';
            }
        };
        return true;
    }

    /**
     * TODO 经营者/法人身份证信息/通行证
     * @param $item
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Qinii
     * @day 6/22/21
     */
    protected function checkIdDocInfo($item,$rule,$data)
    {
        if(in_array($data['organization_type'],['2401','2500']) && !empty($item)) return '小微/个人卖家可选证件类型：身份证';

        if($data['id_doc_type'] !== 1){
            if(empty($item)) return '经营者/法人身份证信息为空';

            if(!isset($item['id_doc_name']) || empty($item['id_doc_name'])) return '证件姓名为空';
            if(!isset($item['id_doc_number']) || empty($item['id_doc_number'])) return '证件号码为空';
            if(!isset($item['id_doc_copy']) || empty($item['id_doc_copy'])) return '经营者/法人其他类型证件信息证件正面照片为空';
            if($data['id_doc_type'] !== 2)   //护照不需要传反面
            {
                if(!isset($item['id_doc_copy_back']) || empty($item['id_doc_copy_back'])) return '经营者/法人其他类型证件信息证件反面照片为空';
            }
            if(!isset($item['doc_period_begin']) || empty($item['doc_period_begin'])) return '经营者/法人其他类型证件信息证件有效期开始时间为空';
            if(!isset($item['doc_period_end']) || empty($item['doc_period_end'])) return '经营者/法人其他类型证件信息证件结束日期为空';

            if($item['doc_period_end'] !== '长期') {
                $statr = time();
                $end = strtotime($item['doc_period_end']);
                $t = $end - $statr;
                if(($t/(3600 * 24)) <= 60) return '经营者/法人其他类型证件信息证件结束日期必须大于60天，即结束时间距当前时间需超过60天';
                if(strtotime($item['doc_period_begin']) >= strtotime($item['doc_period_end'])) return '经营者/法人其他类型证件信息证件结束日期必须大于证件开始时间';
                if($data['organization_type']  === 2){
                    if(!isset($item['id_doc_address']) || empty($item['id_doc_address'])) return '经营者/法人其他类型证件信息证件居住地址为空';
                }
            }
        }

        return true;
    }

    /**
     * TODO 结算银行账户
     * @param $item
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Qinii
     * @day 6/22/21
     */
    protected function getAccountInfo($item,$rule,$data)
    {
//        if($data['need_account_info']){

            if(empty($item)) return '结算银行账户信息为空';

            if(!isset($item['bank_account_type']) || empty($item['bank_account_type'])) return '账户类型为空';
            if(!isset($item['account_bank']) || empty($item['account_bank'])) return '开户银行为空';
            if(!isset($item['account_name']) || empty($item['account_name'])) return '开户名称为空';
            if(!isset($item['bank_address_code']) || empty($item['bank_address_code'])) return '开户银行省市编码为空';
            if(!isset($item['account_number']) || empty($item['account_number'])) return '银行帐号为空';

//        }

        return true;
    }

    /**
     * TODO 超级管理员信息
     * @param $item
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Qinii
     * @day 6/22/21
     */
    protected function getContactInfo($item,$rule,$data)
    {

        if(empty($item)) return '超级管理员信息信息为空';

        if(!isset($item['contact_type']) || empty($item['contact_type'])) return '超级管理员类型为空';
        if(!isset($item['contact_name']) || empty($item['contact_name'])) return '超级管理员姓名为空';
        if(!isset($item['contact_id_card_number']) || empty($item['contact_id_card_number'])) return '超级管理员身份证件号码为空';
        if(!isset($item['mobile_phone']) || empty($item['mobile_phone'])) return '超级管理员手机为空';

        if(!in_array($data['organization_type'],['2401','2500'])){
            if(!isset($item['contact_email']) || empty($item['contact_email'])) return '邮箱为空';
        }

        if($item['contact_type'] === 66)  //当超级管理员类型为66（经办人时）
        {
            if(!isset($item['contact_id_doc_type']) || empty($item['contact_id_doc_type']) || !in_array($item['contact_id_doc_type'],[1,2,3,4,5,6,7,8])) return '超级管理员证件类型为空或不合法';
            if(!isset($item['contact_id_doc_copy']) || empty($item['contact_id_doc_copy'])) return '超级管理员信息证件正面照片为空';
            if($item['contact_id_doc_type'] !== 2)   //护照不需要传反面
            {
                if(!isset($item['contact_id_doc_copy_back']) || empty($item['contact_id_doc_copy_back'])) return '超级管理员信息证件反面照片为空';
            }
            if(!isset($item['contact_id_doc_period_begin']) || empty($item['contact_id_doc_period_begin'])) return '超级管理员信息证件有效期开始时间为空';
            if(!isset($item['contact_id_doc_period_end']) || empty($item['contact_id_doc_period_end'])) return '超级管理员信息证件结束日期为空';

            if($item['contact_id_doc_period_end'] !== '长期') {
                $statr = time();
                $end = strtotime($item['contact_id_doc_period_end']);
                $t = $end - $statr;
                if(($t/(3600 * 24)) <= 60) return '超级管理员信息证件结束日期必须大于60天，即结束时间距当前时间需超过60天';
                if(strtotime($item['contact_id_doc_period_begin']) >= strtotime($item['contact_id_doc_period_end'])) return '超级管理员信息证件结束日期必须大于证件开始时间';
            }
            if(!isset($item['business_authorization_letter']) || empty($item['business_authorization_letter'])) return '超级管理员信息业务办理授权函为空';
        }

        return true;
    }

    /**
     * TODO 店铺信息
     * @param $item
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Qinii
     * @day 6/22/21
     */
    protected function checkSalesSceneInfo($item,$rule,$data)
    {
        if(empty($item)) return '店铺信息为空';

        if(!isset($item['store_name']) || empty($item['store_name'])) return '店铺名称为空';

        if(!isset($item['store_url']) && !isset($item['store_url'])) return '店铺链接和店铺二维码二选一';

        return true;
    }

    /**
     * TODO 补充说明s
     * @param $item
     * @param $rule
     * @param $data
     * @return bool|string
     * @author Qinii
     * @day 6/24/21
     */
    protected function checkBusinessAdditionDesc($item,$rule,$data)
    {
        if($data['organization_type'] == 2500 && empty($item)) return '若主体为“个人卖家”:补充说明不能为空';
        return true;
    }

}
