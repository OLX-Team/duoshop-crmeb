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


namespace crmeb\services\easywechat\batches;


use crmeb\services\easywechat\BaseClient;
use think\exception\ValidateException;

class Client extends BaseClient
{
    protected $isService = false;

    /**
     * 商家转账到零钱
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter4_3_1.shtml
     * @param $type
     * @param array $order
     * @return mixed
     */
    public function send(array $order)
    {
        $params = [
            'appid'        => $this->app['config']['app_id'],
            'out_batch_no' => $order['out_batch_no'],
            'batch_name'   => $order['batch_name'],
            'batch_remark' => $order['batch_remark'],
            'total_amount' => $order['total_amount'],
            'total_num'    => $order['total_num'],
            'transfer_detail_list' => $order['transfer_detail_list'],
        ];
        $content = json_encode($params, JSON_UNESCAPED_UNICODE);

        $res = $this->request('/v3/transfer/batches', 'POST', ['sign_body' => $content]);
        if (isset($res['code'])) {
            throw new ValidateException('微信接口报错:' . $res['message']);
        }
        return $res;
    }

}
