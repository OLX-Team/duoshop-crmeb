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

namespace crmeb\services\sms\storage;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use app\common\repositories\system\notice\SystemNoticeConfigRepository;
use crmeb\services\BaseSmss;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;
use Darabonba\OpenApi\Models\Config as AliConfig;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use think\exception\ValidateException;
use think\facade\Config;



/**
 * Class Aliyun
 * @package crmeb\services\sms\storage
 */
class Aliyun extends BaseSmss
{
    protected  $AccessKeySecret = '';
    /**
     * @param array $config
     * @return mixed|void
     */
    protected function initialize(array $config = [])
    {

        parent::initialize($config);
    }

    public static function createClient($accessKeyId, $accessKeySecret){
        $config = new AliConfig([
            // 您的 AccessKey ID
            "accessKeyId" => systemConfig('aliyun_AccessKeyId'),
            // 您的 AccessKey Secret
            "accessKeySecret" => systemConfig('aliyun_AccessKeySecret')
        ]);
        // 访问的域名
        $config->endpoint = "dysmsapi.aliyuncs.com";
        return new Dysmsapi($config);
    }


    /**
     * @param string $phone
     * @param string $templateId
     * @param array $data
     * @return array[]|bool|mixed
     */
    public function send(string $phone, string $templateId, array $data = [])
    {
        if (empty($phone)) {
            return $this->setError('电话号码不能为空');
        }
        //验证码只支持一个参数
        if ($templateId == 'VERIFICATION_CODE') {
            unset($data['time']);
        }
        if (isset($data['site'])) {
            unset($data['site']);
        }
        $client = self::createClient("accessKeyId", "accessKeySecret");
        $temp = app()->make(SystemNoticeConfigRepository::class)->getSmsTemplate($templateId);
        if (!$temp) throw new ValidateException('模板不存在：'. $templateId);
        $sendSmsRequest = new SendSmsRequest([
            "phoneNumbers" => $phone,
            "signName" => systemConfig('aliyun_SignName'),
            "templateCode" => $temp,
            "templateParam" => json_encode($data),
        ]);
        $runtime = new RuntimeOptions([]);
        try {
            // 复制代码运行请自行打印 API 的返回值
            $resp = $client->sendSmsWithOptions($sendSmsRequest, $runtime);
        }
        catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            // 如有需要，请打印 error
            throw new ValidateException('【阿里云平台错误提示】：'.$error->message);
            //$resp = Utils::assertAsString($error->message);
        }
        if (isset($resp) && $resp->body->code !== 'OK') {
            throw new ValidateException('【阿里云平台错误提示】：'.$resp->body->message);
        }
        return 'ok';
    }

    public function open(){}

    public function modify(string $sign = null , string $phone, string $code){}

    public function info(){}

    public function temps(int $page = 0, int $limit = 10, int $type = 1){}

    public function apply(string $title, string $content, int $type){}

    public function applys(int $tempType, int $page, int $limit){}

    public function record($record_id){}
}
