<?php

namespace UCloud\Http;

class Response {
    public $StatusCode;
    public $Header;
    public $ContentLength;
    public $Body;
    public $Timeout;

    public function __construct($code, $body, $timeout = null) {
        $this->StatusCode = $code;
        $this->Header = array();
        $this->Body = $body;
        $this->ContentLength = strlen($body);
        $this->Timeout = $timeout;
    }
}