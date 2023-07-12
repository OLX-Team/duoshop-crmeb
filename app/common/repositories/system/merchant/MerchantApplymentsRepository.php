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

namespace app\common\repositories\system\merchant;

use app\common\dao\system\merchant\MerchantAppymentsDao;
use app\common\model\system\merchant\MerchantApplyments;
use app\common\repositories\BaseRepository;
use crmeb\jobs\SendSmsJob;
use crmeb\services\ImageWaterMarkService;
use crmeb\services\SmsService;
use crmeb\services\UploadService;
use crmeb\services\WechatService;
use crmeb\services\YunxinSmsService;
use FormBuilder\Factory\Elm;
use function Symfony\Component\String\b;
use think\db\concern\Transaction;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Route;

class MerchantApplymentsRepository extends BaseRepository
{

    const IDCARD = 'IDENTIFICATION_TYPE_MAINLAND_IDCARD';   //：中国大陆居民-身份证
    const PASSPORT = 'IDENTIFICATION_TYPE_OVERSEA_PASSPORT';  //：其他国家或地区居民-护照
    const HONGKONG = 'IDENTIFICATION_TYPE_HONGKONG';          //：中国香港居民–来往内地通行证
    const MACAO = 'IDENTIFICATION_TYPE_MACAO';             //：中国澳门居民–来往内地通行证
    const TAIWAN = 'IDENTIFICATION_TYPE_TAIWAN';            //：中国台湾居民–来往大陆通行证
    const FOREIGN_RESIDENT = 'IDENTIFICATION_TYPE_FOREIGN_RESIDENT';            //：外国人居留证
    const MACAO_RESIDENT = 'IDENTIFICATION_TYPE_HONGKONG_MACAO_RESIDENT';            //：港澳居民证
    const TAIWAN_RESIDENT = 'IDENTIFICATION_TYPE_TAIWAN_RESIDENT';            //：台湾居民证

    public function __construct(MerchantAppymentsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 申请
     * @param array $data
     * @param $merId
     * @return mixed
     * @author Qinii
     * @day 6/23/21
     */
    public function create(array $data,$merId)
    {
        $count = $this->dao->getSearch(['mer_id' => $merId])->count('*');
        if($count) throw new ValidateException('此商户已存在申请信息');

        $out_request_no = $this->getOutRequestNo($merId);
        $ret['mer_name'] = $data['merchant_shortname'];
        $ret['out_request_no'] = $out_request_no;
        $data['out_request_no'] = $out_request_no;
        $ret['info'] = json_encode($data,JSON_UNESCAPED_UNICODE);
        $ret['mer_id'] = $merId;

        $this->dao->create($ret);
    }

    /**
     * TODO 整理请求数据
     * @param $info
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function sltData($info)
    {
        foreach ($info as $key => $value){
            if(is_object($value)){
                $value = (array)$value;
            }
            $data[$key] = $value;
        }
        if (isset($data['need_account_info'])) unset($data['need_account_info']);
        $data['id_doc_type'] = $this->getIdDocType($data['id_doc_type']);

        //营业执照
        if(isset($data['business_license_info'])){
            if(isset($data['business_license_info']['business_license_copy'])) {
                $business_license_copy = $data['business_license_info']['business_license_copy']->media_id;
                unset($data['business_license_info']['business_license_copy']);
                $data['business_license_info']['business_license_copy'] = $business_license_copy;
            }
            if(isset($data['business_license_info']['business_time'])){
                $organization_time = json_encode($data['business_license_info']['business_time'],JSON_UNESCAPED_UNICODE);
                $data['business_license_info']['business_time'] = $organization_time;
            }
        }

        //组织机构代码
        if(isset($data['organization_cert_info'])){
            if(isset($data['organization_cert_info']['organization_copy'])) {
                $organization_copy = $data['organization_cert_info']['organization_copy']->media_id;
                unset($data['organization_cert_info']['organization_copy']);
                $data['organization_cert_info']['organization_copy'] = $organization_copy;
            }
            if(isset($data['organization_cert_info']['organization_time'])){
                $organization_time = json_encode($data['organization_cert_info']['organization_time'],JSON_UNESCAPED_UNICODE);
                $data['organization_cert_info']['organization_time'] = $organization_time;
            }
        }

        //身份证
        if(isset($data['id_card_info'])){
            if(isset($data['id_card_info']['id_card_copy'])) {
                $id_card_copy = $data['id_card_info']['id_card_copy']->media_id;
                unset($data['id_card_info']['id_card_copy']);
                $data['id_card_info']['id_card_copy'] = $id_card_copy;
            }
            if(isset($data['id_card_info']['id_card_national'])) {
                $id_card_national = $data['id_card_info']['id_card_national']->media_id;
                unset($data['id_card_info']['id_card_national']);
                $data['id_card_info']['id_card_national'] = $id_card_national;
            }
        }

        //银行
        if(isset($data['account_info'])) {
            if(is_array($data['account_info']['bank_address_code'])){
                $bank_address_code = (string)$data['account_info']['bank_address_code'][2];
                unset($data['account_infoaccount_info']['bank_address_code']);
                $data['account_info']['bank_address_code'] = $bank_address_code;
            }
            $data['account_info']['bank_account_type'] = (string)$data['account_info']['bank_account_type'];
        }

        //管理员
        if(isset($data['contact_info'])) {
            $data['contact_info']['contact_type'] = (string)$data['contact_info']['contact_type'];
            if ($data['contact_info']['contact_type'] == 65) {
                unset(
                    $data['contact_info']['contact_id_doc_copy'],
                    $data['contact_info']['contact_id_doc_copy_back'],
                    $data['contact_info']['contact_id_doc_period_begin'],
                    $data['contact_info']['contact_id_doc_period_end'],
                    $data['contact_info']['business_authorization_letter'],
                    $data['contact_info']['contact_id_doc_type']
                );
            }
            if(isset($data['contact_info']['contact_id_doc_period_end']))
            {
                $doc_ = json_encode($data['contact_info']['contact_id_doc_period_end'],JSON_UNESCAPED_UNICODE);
                $data['contact_info']['contact_id_doc_period_end'] = $doc_;
            }

            if(isset($data['contact_info']['contact_id_doc_type']))
            {
                $data['contact_info']['contact_id_doc_type'] = $this->getIdDocType($data['contact_info']['contact_id_doc_type']);
            }
            if(isset($data['contact_info']['contact_id_doc_copy'])) {
                $contact_id_doc_copy = $data['contact_info']['contact_id_doc_copy']->media_id;
                unset($data['contact_info']['contact_id_doc_copy']);
                $data['contact_info']['contact_id_doc_copy'] = $contact_id_doc_copy;
            }

            if(isset($data['contact_info']['contact_id_doc_copy_back'])) {
                $contact_id_doc_copy_back = $data['contact_info']['contact_id_doc_copy_back']->media_id;
                unset($data['contact_info']['contact_id_doc_copy_back']);
                $data['contact_info']['contact_id_doc_copy_back'] = $contact_id_doc_copy_back;
            }
            if(isset($data['contact_info']['business_authorization_letter'])) {
                $business_authorization_letter = $data['contact_info']['business_authorization_letter']->media_id;
                unset($data['contact_info']['business_authorization_letter']);
                $data['contact_info']['business_authorization_letter'] = $business_authorization_letter;
            }
        }

        //其他证件
        if(isset($data['id_doc_info'])){
            $doc_ = json_encode($data['id_doc_info']['doc_period_end'],JSON_UNESCAPED_UNICODE);
            $data['id_doc_info']['doc_period_end'] = $doc_;

            if(isset($data['id_doc_info']['id_doc_copy'])) {
                $id_doc_copy = $data['id_doc_info']['id_doc_copy']->media_id;
                unset($data['id_doc_info']['id_doc_copy']);
                $data['id_doc_info']['id_doc_copy'] = $id_doc_copy;
            }
            if(isset($data['id_doc_info']['id_doc_copy_back'])) {
                $id_doc_copy_back = $data['id_doc_info']['id_doc_copy_back']->media_id;
                unset($data['id_doc_info']['id_doc_copy_back']);
                $data['id_doc_info']['id_doc_copy_back'] = $id_doc_copy_back;
            }
        }

        //店铺信息
        if(isset($data['sales_scene_info']['store_qr_code']) && $data['sales_scene_info']['store_qr_code']){
            $store_qr_code= $data['sales_scene_info']['store_qr_code']->media_id;
            unset($data['sales_scene_info']['store_qr_code']);
            $data['sales_scene_info']['store_qr_code'] = $store_qr_code;
        }

        //特殊资质
        if(isset($data['qualifications']) && !empty($data['qualifications'])){
            $qualifications = [];
            foreach ($data['qualifications'] as $item){
                $qualifications[] = $item->media_id;
            }
            unset($data['qualifications']);
            $data['qualifications'] = json_encode($qualifications,JSON_UNESCAPED_UNICODE);
        }

        //补充材料
        if(isset($data['business_addition_pics']) && !empty($data['business_addition_pics'])){
            $business_addition_pics = [];
            foreach ($data['business_addition_pics'] as $item){
                $business_addition_pics[] =  $item->media_id;
            }
            unset($data['business_addition_pics']);
            $data['business_addition_pics'] =  json_encode($business_addition_pics,JSON_UNESCAPED_UNICODE);
        }
        $data['organization_type'] = (string)$data['organization_type'];
        return $data;
    }

    /**
     * TODO 生成申请单
     * @param $merId
     * @return string
     * @author Qinii
     * @day 6/24/21
     */
    public function getOutRequestNo($merId)
    {

        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $key = 'MERCHANT' . $merId . '_' . $msectime . mt_rand(10000, max(intval($msec * 10000) + 10000, 98369));

        do{
            $ret = $this->dao->getSearch(['out_request_no' => $key])->count();
        }while($ret);

        return $key;
    }

    /**
     * TODO 详情
     * @param $id
     * @param $merId
     * @return array|\think\Model|null
     * @author Qinii
     * @day 6/22/21
     */
    public function detail(int $merId)
    {
        if($merId) $where['mer_id'] = $merId;

        $data = $this->dao->getSearch($where)->find();

        if(!$data) return [];
        $data['info'] = json_decode($data['info']);
        return $data;
    }

    /**
     * TODO 编辑
     * @param $id
     * @param $data
     * @author Qinii
     * @day 6/22/21
     */
    public function edit($id,$data)
    {
        //申请状态: 0.平台未提交，-1.平台驳回，10.平台提交审核中，11.需用户操作 ，20.已完成，30.已冻结，40.驳回
        $get = $this->dao->get($id);
        if(!$get) throw new ValidateException('数据不存在');
        if(!in_array($get['status'],[-1,0,40])) throw new ValidateException('数据当前状态不可编辑');

        $data['out_request_no'] = $get['out_request_no'];
        $ret['info'] = json_encode($data,JSON_UNESCAPED_UNICODE);
        $ret['status'] = 0;
        $ret['message'] = '';
        $this->dao->update($id,$ret);
    }

    /**
     * TODO 查询申请状态
     * @param $merId
     * @author Qinii
     * @day 6/23/21
     */
    public function check($merId)
    {
        $ret = $this->dao->getSearch(['mer_id' => $merId])->find();
        $data = [];
        if($ret['status'] < 10) throw new ValidateException('平台审核中...');
        if($ret['status'] == 20) throw new ValidateException('申请已完成，请勿重复查询');
        try{
            $data = WechatService::create()->applyments()->getApplicationById($ret->applyment_id);
        }catch (\Exception $exception){

        }
        if(!$data){
            $data = WechatService::create()->applyments()->getApplicationByNo($ret->out_request_no);
            if($data){
                $ret->applyment_id = $data['applyment_id'];
                $ret->save();
            }
        }
        if($data) {
            $result = $this->getApplymentState($data);
            $this->sendSms($ret,$result['status']);
            return Db::transaction(function () use ($merId, $ret, $result) {
                if (isset($result['sub_mchid'])) $this->profitsharingAdd($ret,$result);
                $this->dao->update($ret->mer_applyments_id, $result);
            });
        }else{
            return ;
        }
    }

    /**
     * TODO 添加分账商户
     * @param MerchantApplyments $ret
     * @param $result
     * @author Qinii
     * @day 6/24/21
     */
    public function profitsharingAdd(MerchantApplyments $ret,$result)
    {
        $info = json_decode($ret['info']);
        $profitsharing = [
            "type" => 'MERCHANT_ID',
            "account" =>  $result['sub_mchid'],
            // "name" => $info->account_info->account_name,
            "relation_type" => "PLATFORM"
        ];
        $res = WechatService::create()->applyments()->profitsharingAdd($profitsharing);
        if(isset($res['account'])) app()->make(MerchantRepository::class)->update($ret->mer_id, ['sub_mchid' => $res['account'] ]);
    }

    /**
     * TODO 查询返回的状态整理
     * @param $data
     * @return array
     * @author Qinii
     * @day 6/23/21
     */
    public function getApplymentState($data)
    {
        //CHECKING：资料校验中
        //ACCOUNT_NEED_VERIFY：待账户验证
        //AUDITING：审核中
        //REJECTED：已驳回
        //NEED_SIGN：待签约
        //FINISH：完成
        //FROZEN：已冻结
        $result = [];
        $message = '';
        $status = 10;
        switch (($data['applyment_state']))
        {
            //申请状态: 0.平台未提交，-1.平台驳回，10.平台提交审核中，11.需用户操作 ，20.已完成，30.已冻结，40.驳回
            case 'ACCOUNT_NEED_VERIFY':
                $status = 11;
                if(isset($data['account_validation'])){
                    $ret = $data['account_validation'];
                    $message = '通过申请银行账号向以下信息汇款完成验证,'.PHP_EOL;
                    $message .= '收款方信息：'.PHP_EOL;
                    $message .= "汇款金额：".$ret['pay_amount'].'（单位：分）;'.PHP_EOL;
                    $message .= "收款卡号：".$ret['destination_account_number'].';'.PHP_EOL;
                    $message .= "收款户名：".$ret['destination_account_name'].';'.PHP_EOL;
                    $message .= "开户银行：".$ret['destination_account_bank'].';'.PHP_EOL;
                    $message .= "省市信息：".$ret['city'].';'.PHP_EOL;
                    $message .= "备注信息：".$ret['remark'].';'.PHP_EOL;
                    $message .= "汇款截止时间：".$ret['deadline'].'。';
                }
                if(isset($data['legal_validation_url'])){
                    $message = '商户法人通过此链接完成验证：'.$data['legal_validation_url'];
                }
                break;
            case 'REJECTED':
                $message = '';
                foreach ($data['audit_detail'] as $datum){
                    $message .= '参数名称:'.$datum['param_name'].PHP_EOL;
                    $message .= '驳回原因:'.$datum['reject_reason'].PHP_EOL;
                }
                $status = 40;
                break;
            case 'NEED_SIGN':
                $status = 11;
                $message = $data['sign_url'];
                break;
            case 'FINISH':
                $result['sub_mchid'] = $data['sub_mchid'];
                $status = 20;
                $message = '完成';
                break;
            case 'FROZEN':
                $status = 30;
                break;
            default:
                break;
        }
        $result['status'] = $status;
        $result['message'] = $message;
        return $result;
    }

    /**
     * TODO 上传图片
     * @param $field
     * @return array
     * @author Qinii
     * @day 6/21/21
     */
    public function uploadImage($field,$water)
    {
        $upload = UploadService::create(1);
        $info = $upload->to('def')->move($field);
        if ($info === false) throw new ValidateException($upload->getError());
        $res = $upload->getUploadInfo();
        $res['path'] = app()->getRootPath().'public'.($res['dir']);
        $res['dir'] = tidy_url($res['dir']);
        if($res['path']) $ret = WechatService::create()->uploadImages([$res]);
        if(!$water) app()->make(ImageWaterMarkService::class)->run($res['path']);
        return $ret;
    }

    /**
     * TODO 列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 6/24/21
     */
    public function getList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where)->with(['merchant' => function($query){
            $query->field('mer_id,mer_name');
        }])->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    /**
     * TODO 审核操作
     * @param int $id
     * @param array $data
     * @author Qinii
     * @day 6/23/21
     */
    public function switchWithStatus(int $id,array $data)
    {
        $ret = $this->dao->get($id);
        if(!$ret) throw new ValidateException('数据不存在');
        if($ret['status'] !== 0) throw new ValidateException('请勿重复审核');

        if($data['status'] == 10){
            $info = $this->sltData(json_decode($ret['info']));
            Db::transaction(function() use($id,$info){
                WechatService::create()->applyments()->submitApplication($info);
                $this->dao->update($id,['status' => 10]);
            });
        }
        if($data['status'] == -1) {
            $this->dao->update($id,$data);
            $this->sendSms($ret,-1);
        }

        return ;
    }

    /**
     * TODO 发送短信
     * @param MerchantApplyments $ret
     * @param $type
     * @author Qinii
     * @day 7/9/21
     */
    public function sendSms(MerchantApplyments $ret,$type)
    {
        if(!systemConfig('applyments_sms')) return ;
        $info = json_decode($ret['info']);
        switch ($type)
        {
            case -1:
                $tmp = 'APPLYMENTS_FAIL';
                break;
            case 11:
                $tmp = 'APPLYMENTS_SIGN';
                break;
            case 20:
                $tmp = 'APPLYMENTS_SUCCESS';
                break;
            case 40:
                $tmp = 'APPLYMENTS_FAIL';
                break;
            default:
                return ;
                break;
        }
        Queue::push(SendSmsJob::class,['tempId' => $tmp, 'id' => ['phone'=> $info->contact_info->mobile_phone, 'mer_name' => $info->merchant_shortname]]);
    }

    /**
     * TODO 查询商户的分账信息
     * @param $merId
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function getMerchant($merId)
    {
        $data = app()->make(MerchantRepository::class)->get($merId);
        if(!$data) throw new ValidateException('数据不存在');
        if(!$data['sub_mchid']) throw new ValidateException('该商户不是分账商户');
        $ret = WechatService::create()->applyments()->getSubMerchant($data['sub_mchid']);

        return $ret;
    }

    /**
     * TODO 备注
     * @param $id
     * @return \FormBuilder\Form
     * @author Qinii
     * @day 7/5/21
     */
    public function markForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('systemMerchantApplymentsMarrkSave', ['id' => $id])->build());
        $form->setRule([
            Elm::text('mark', '备注', $data['mark'])->required(),
        ]);
        return $form->setTitle('修改备注');
    }

    /**
     * TODO 经营者/法人证件类型
     * @param $key
     * @return array|mixed
     * @author Qinii
     * @day 6/22/21
     */
    public function getIdDocType($key)
    {
        $data = [
            1 => self::IDCARD,
            2 => self::PASSPORT,
            3 => self::HONGKONG,
            4 => self::MACAO,
            5 => self::TAIWAN,
            6 => self::FOREIGN_RESIDENT,
            7 => self::MACAO_RESIDENT,
            8 => self::TAIWAN_RESIDENT,
        ];
        if($key) return $data[$key];
        return $data;
    }
}
