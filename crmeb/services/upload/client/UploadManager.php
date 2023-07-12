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
namespace crmeb\services\upload\client;

use UCloud\ActionType;
use UCloud\Auth;
use UCloud\Config;
use UCloud\Http\Client;
use UCloud\Http\Error;
use UCloud\Http\Request;
use  UCloud\Storage\UploadManager as UcloudUploadManager;

class UploadManager extends UcloudUploadManager
{

    public $auth;
    public function __construct(Auth $auth = null) {
       parent::__construct($auth);
       $this->auth = $auth;
    }


    public function MultipartForm($bucket, $key, $file) {
        $action_type = ActionType::POSTFILE;
        $err = \UCloud\CheckConfig($this->auth, ActionType::POSTFILE);
        if ($err != null) {
            return array(null, $err);
        }

        $f = @fopen($file, "r");
        if (!$f) return array(null, new Error(-1, -1, "open $file error"));

        $host = $bucket . $this->auth->ProxySuffix;
        $path = "";
        $fsize = filesize($file);
        $content = "";
        if ($fsize != 0) {
            $content = @fread($f, filesize($file));
            if ($content == FALSE) {
                fclose($f);
                return array(null, new Error(0, -1, "read file error"));
            }
        }
        list($mimetype, $err) = \UCloud\GetFileMimeType($file);
        if ($err) {
            fclose($f);
            return array("", $err);
        }

        $req = new Request('POST', array('host'=>$host, 'path'=>$path), $content, $bucket, $key, $action_type);
        $req->Header['Expect'] = '';
        $token = $this->auth->SignRequest($req, $mimetype);

        $fields = array('Authorization'=>$token, 'FileName' => $key);
        $files  = array('files'=>array('file', $file, $content, $mimetype));

        $client = new Client($this->auth, Config::NO_AUTH_CHECK);
        list($data, $err) = $client->CallWithMultipartForm($req, $fields, $files);
        fclose($f);
        return array($data, $err);
    }

}
