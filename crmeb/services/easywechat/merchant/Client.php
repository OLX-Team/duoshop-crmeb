<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace crmeb\services\easywechat\merchant;

use crmeb\services\easywechat\BaseClient;
use think\exception\ValidateException;

/**
 * Class Client.
 *
 * @author ClouderSky <clouder.flow@gmail.com>
 */
class Client extends BaseClient
{

    /**
     * TODO 二级商户进件成为微信支付商户
     * @param $params
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function submitApplication($params)
    {
        $params = $this->processParams($params);
        $res = $this->request('/v3/ecommerce/applyments/', 'POST', ['sign_body' => json_encode($params, JSON_UNESCAPED_UNICODE)], true);
        if(isset($res['code'])) throw new ValidateException('[微信接口返回]:' . $res['message']);

        return $res;
    }

    /**
     * TODO 申请单ID查询申请状态
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function getApplicationById($id)
    {
        $url = '/v3/ecommerce/applyments/'.$id;
        $res = $this->request($url, 'GET');

        if(isset($res['code'])) throw new ValidateException('[微信接口返回]:' . $res['message']);

        return $res;
    }

    /**
     * TODO 业务申请编号查询申请状
     * @param $no
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function getApplicationByNo($no)
    {
        $url = '/v3/ecommerce/applyments/out-request-no/'.$no;
        $res = $this->request($url, 'GET');

        if(isset($res['code'])) throw new ValidateException('[微信接口返回]:' . $res['message']);

        return $res;
    }

    /**
     * TODO 修改结算账号
     * @param $mchid
     * @param $params
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function updateSubMerchat($mchid,$params)
    {
        $url = "/v3/apply4sub/sub_merchants/{$mchid}/modify-settlement";
        $res = $this->request($url, 'POST',['sign_body' => json_encode($params, JSON_UNESCAPED_UNICODE)], true);
        if(isset($res['code'])) throw new ValidateException('[微信接口返回]:' . $res['message']);

        return $res;
    }

    /**
     * TODO 查询结算账户
     * @param $mchid
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function getSubMerchant($mchid)
    {
        $url = "/v3/apply4sub/sub_merchants/{$mchid}/settlement";
        $res = $this->request($url, 'GET');
        if(isset($res['code'])) throw new ValidateException('[微信接口返回]:' . $res['message']);

        return $res;
    }

    /**
     * TODO 添加分账接收方
     * @param array $params
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function profitsharingAdd(array $params)
    {
        $url = '/v3/ecommerce/profitsharing/receivers/add';

        $app_id = !empty($this->app->config->app_id) ? $this->app->config->app_id : $this->app->config->routine_appId;

        $params['appid'] = $app_id;

        $options['sign_body'] = json_encode($params,JSON_UNESCAPED_UNICODE);

        $res = $this->request($url, 'POST',$options,true);

        if(isset($res['code'])) throw new ValidateException('[微信接口返回]:' . $res['message']);

        return $res;
    }

    /**
     * TODO 删除分账接收方
     * @param array $params
     * @return mixed
     * @author Qinii
     * @day 6/24/21
     */
    public function profitsharingDel(array $params)
    {
        $url = '/v3/ecommerce/profitsharing/receivers/delete';

        $app_id = !empty($this->app->config->app_id) ? $this->app->config->app_id : $this->app->config->routine_appId;

        $params['appid'] = $app_id;

        $options['sign_body'] = json_encode($params,JSON_UNESCAPED_UNICODE);

        $res = $this->request($url, 'POST',$options,true);

        if(isset($res['code'])) throw new ValidateException('[微信接口返回]:' . $res['message']);

        return $res;
    }

    /**
     * TODO 上传图片
     * @param $filepath
     * @param $filename
     * @author Qinii
     * @day 6/21/21
     */
    public function upload($filepath,$filename)
    {

        $boundary = uniqid();
        try{
           // $file = file_get_contents($filepath);
            $file = fread(fopen($filepath,'r'),filesize($filepath));
        }catch (\Exception $exception){
            throw new ValidateException($exception->getMessage());
        }


        $options['headers'] = ['Content-Type' => 'multipart/form-data;boundary='.$boundary];

        $options['sign_body'] = json_encode(['filename' => $filename,'sha256' => hash_file("sha256",$filepath)]);

        $boundaryStr = "--{$boundary}\r\n";

        $body = $boundaryStr;
        $body .= 'Content-Disposition: form-data; name="meta"'."\r\n";
        $body .= 'Content-Type: application/json'."\r\n";
        $body .= "\r\n";
        $body .= $options['sign_body']."\r\n";
        $body .= $boundaryStr;
        $body .= 'Content-Disposition: form-data; name="file"; filename="'.$filename.'"'."\r\n";
        $body .= 'Content-Type: image/jpeg'.';'."\r\n";
        $body .= "\r\n";
        $body .= $file."\r\n";
        $body .= "--{$boundary}--";

        $options['data'] = (($body));

        try {
            $res = $this->request('/v3/merchant/media/upload', 'POST', $options, true);
        }catch(\Exception $exception){
            throw new ValidateException($exception->getMessage());
        }

        if(isset($res['code'])) throw new ValidateException('[微信接口返回]:' . $res['message']);

        return $res;

    }
}
