<?php

namespace UCloud\Http;

use UCloud\Config;
use UCloud\ActionType;

class Request {
    public $URL;
    public $RawQuerys;
    public $Header;
    public $Body;
    public $UA;
    public $METHOD;
    public $Params;      //map
    public $Bucket;
    public $Key;
    public $Timeout;

    public function __construct($method, $url, $body, $bucket, $key, $action_type = ActionType::NONE, $timeout = null) {
        $this->URL    = $url;
        if (isset($url["query"])) {
            $this->RawQuerys = $url["query"];
        }
        $this->Header = array();
        $this->Body   = $body;
        $this->UA     = $this->UserAgent();
        $this->METHOD = $method;
        $this->Bucket = $bucket;
        $this->Key    = $key;

        if ($timeout == null && $action_type !== ActionType::PUTFILE && $action_type !== ActionType::POSTFILE) {
            $timeout = 10;
        }
        $this->Timeout = $timeout;
    }

    function UserAgent() {
        $SDK_VER = Config::SDK_VER;
        $sdkInfo = "UCloudPHP/$SDK_VER";

        $systemInfo = php_uname("s");
        $machineInfo = php_uname("m");

        $envInfo = "($systemInfo/$machineInfo)";

        $phpVer = phpversion();

        $ua = "$sdkInfo $envInfo PHP/$phpVer";
        return $ua;
    }

    public function EncodedQuery() {
        if ($this->RawQuerys != null) {
            $q = "";
            foreach ($this->RawQuerys as $k => $v) {
                $q = $q . "&" . rawurlencode($k) . "=" . rawurlencode($v);
            }
            return $q;
        }
        return "";
    }
}