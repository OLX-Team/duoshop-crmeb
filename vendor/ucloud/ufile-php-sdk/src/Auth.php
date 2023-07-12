<?php

namespace UCloud;

class Auth {
    public $PublicKey;
    public $PrivateKey;
    public $ProxySuffix;

    public function __construct($publicKey, $privateKey, $proxySuffix) {
        $this->PublicKey = $publicKey;
        $this->PrivateKey = $privateKey;
        $this->ProxySuffix = $proxySuffix;
    }

    private function CanonicalizedResource($bucket, $key) {
        return "/" . $bucket . "/" . $key;
    }

    private function CanonicalizedUCloudHeaders($headers) {
        $keys = array();
        foreach($headers as $header) {
            $header = trim($header);
            $arr = explode(':', $header);
            if (count($arr) < 2) {
                continue;
            }
            list($k, $v) = $arr;
            $k = strtolower($k);
            if (strncasecmp($k, "x-ucloud") === 0) {
                $keys[] = $k;
            }
        }

        $c = '';
        sort($keys, SORT_STRING);
        foreach($keys as $k) {
            $c .= $k . ":" . trim($headers[$v], " ") . "\n";
        }
        return $c;
    }

    public function Sign($data) {
        $sign = base64_encode(hash_hmac('sha1', $data, $this->PrivateKey, true));
        return "UCloud " . $this->PublicKey . ":" . $sign;
    }

    //@results: $token
    public function SignRequest($req, $mimetype = null, $type = Config::HEAD_FIELD_CHECK) {
        $url = $req->URL;
        $url = parse_url($url['path']);
        $data = '';
        $data .= strtoupper($req->METHOD) . "\n";
        $data .= Header_Get($req->Header, 'Content-MD5') . "\n";
        if ($mimetype)
            $data .=  $mimetype . "\n";
        else
            $data .= Header_Get($req->Header, 'Content-Type') . "\n";
        if ($type === Config::HEAD_FIELD_CHECK)
            $data .= Header_Get($req->Header, 'Date') . "\n";
        else
            $data .= Header_Get($req->Header, 'Expires') . "\n";
        $data .= $this->CanonicalizedUCloudHeaders($req->Header);
        $data .= $this->CanonicalizedResource($req->Bucket, $req->Key);
        return $this->Sign($data);
    }
}
