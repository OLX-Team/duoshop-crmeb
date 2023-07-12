<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace crmeb\services\easywechat\msgseccheck;

use crmeb\services\easywechat\BaseClient;
use EasyWeChat\Core\AbstractAPI;
use EasyWeChat\Core\AccessToken;
use EasyWeChat\Core\Exceptions\HttpException;
use EasyWeChat\Core\Http;
use EasyWeChat\Payment\API;
use EasyWeChat\Payment\Merchant;
use GuzzleHttp\HandlerStack;
use think\Exception;
use EasyWeChat\Support\XML;
use EasyWeChat\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use think\exception\ValidateException;

/**
 * Class Client.
 *
 * @author ClouderSky <clouder.flow@gmail.com>
 */
class Client extends BaseClient
{
    protected $isService = false;

    const MSG_API = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=';
    const MEDIA_API = 'https://api.weixin.qq.com/wxa/media_check_async?access_token=';
    const LABEL = [
        100   => '正常',
        10001 => '广告',
        20001 => '时政',
        20002 => '色情',
        20003 => '辱骂',
        20006 => '违法犯罪',
        20008 => '欺诈',
        20012 => '低俗',
        20013 => '版权',
        21000 => '其他'
    ];

    public function msgSecCheck($content, $scene, $openId)
    {
       $access_token = $this->accessToken->getToken();
        //scene 场景枚举值（1 资料；2 评论；3 论坛；4 社交日志）
        $_url = self::MSG_API.$access_token;
        $params = [
            'content' => $content,
            'version' => (int)2,
            'scene' => (int)$scene,
            'openid' => $openId,
        ];

        try{
            $res = $this->parseJSON('json',[$_url, $params]);
            if (isset($res->errcode) && $res->errcode == 0) {
                if($res->result['label'] == 100) {
                    return true;
                } else {
                    throw new ValidateException('内容包含：【'.self::LABEL[$res->result['label']].'】无法发布');
                }
            }
        }catch (Exception $exception) {
            throw new ValidateException($exception->getMessage());
        }
    }


    /**
     * TODO 图片或音频是异步回调，暂未使用
     * @param $media_url
     * @param $scene
     * @param $openId
     * @param $media_type
     * @return bool
     * @author Qinii
     * @day 2023/2/1
     */
    public function mediaSecCheck($media_url,$scene,$openId,$media_type)
    {
        $access_token = $this->accessToken->getToken();
        //$media_type 1:音频;2:图片
        //scene 场景枚举值（1 资料；2 评论；3 论坛；4 社交日志）
        $params = [
            'media_url' => $media_url,
            'media_type' => $media_type,
            'version' => (int)2,
            'scene' => (int)$scene,
            'openid' => $openId,
        ];
        $_url = self::MEDIA_API.$access_token;
        try{
            $res = $this->parseJSON('json',[$_url, $params]);
            if (isset($res->errcode) && $res->errcode == 0) {
                return true;
            } else {
                throw new ValidateException($res->errmsg);
            }
        }catch (Exception $exception) {
            throw new ValidateException($exception->getMessage());
        }
    }
}
