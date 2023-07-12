<?php

namespace UCloud\Http;

class Error {
    public $Code;        // int
    public $ErrRet;	     // int
    public $ErrMsg;	     // string
    public $SessionId;	 // string

    public function __construct($code, $errRet, $errMsg) {
        $this->Code   = $code;
        $this->ErrRet = $errRet;
        $this->ErrMsg = $errMsg;
    }
}