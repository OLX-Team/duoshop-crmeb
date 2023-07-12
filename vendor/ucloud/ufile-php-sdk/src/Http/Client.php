<?php

namespace UCloud\Http;

use UCloud;
use UCloud\Config;

class Client {
    public $Auth;
    public $Type;
    public $MimeType;

    public function __construct($auth, $mimetype = null, $type = Config::HEAD_FIELD_CHECK) {
        $this->Auth = $auth;
        $this->Type = $type;
        $this->MimeType = $mimetype;
    }

    //@results: ($resp, $error)
    public function RoundTrip($req) {
        if ($this->Type === Config::HEAD_FIELD_CHECK) {
            $token = $this->Auth->SignRequest($req, $this->MimeType, $this->Type);
            $req->Header['Authorization'] = $token;
        }
        return $this->ClientDo($req);
    }

    private function ClientDo($req) {
        $ch = curl_init();
        $url = $req->URL;

        $options = array(
            CURLOPT_USERAGENT => $req->UA,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_CUSTOMREQUEST  => $req->METHOD,
            CURLOPT_URL => $url['host'] . "/" . rawurlencode($url['path']) . "?" . $req->EncodedQuery(),
            CURLOPT_TIMEOUT => $req->Timeout,
            CURLOPT_CONNECTTIMEOUT => $req->Timeout
        );

        $httpHeader = $req->Header;
        if (!empty($httpHeader))
        {
            $header = array();
            foreach($httpHeader as $key => $parsedUrlValue) {
                $header[] = "$key: $parsedUrlValue";
            }
            $options[CURLOPT_HTTPHEADER] = $header;
        }
        $body = $req->Body;
        if (!empty($body)) {
            $options[CURLOPT_POSTFIELDS] = $body;
        } else {
            $options[CURLOPT_POSTFIELDS] = "";
        }
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $ret = curl_errno($ch);
        if ($ret !== 0) {
            $err = new Error(0, $ret, curl_error($ch));
            curl_close($ch);
            return array(null, $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $responseArray = explode("\r\n\r\n", $result);
        $responseArraySize = sizeof($responseArray);
        $headerString = $responseArray[$responseArraySize-2];
        $respBody = $responseArray[$responseArraySize-1];

        $headers = $this->parseHeaders($headerString);
        $resp = new Response($code, $respBody);
        $resp->Header = $headers;
        $err = null;
        if (floor($resp->StatusCode/100) != 2) {
            list($r, $m) = $this->parseError($respBody);
            $err = new Error($resp->StatusCode, $r, $m);
        }
        return array($resp, $err);
    }

    private function parseError($bodyString) {
        $r = 0;
        $m = '';
        $mp = json_decode($bodyString);
        if (isset($mp->{'ErrRet'})) {
            $r = $mp->{'ErrRet'};
        }
        if (isset($mp->{'ErrMsg'})) {
            $m = $mp->{'ErrMsg'};
        }
        return array($r, $m);
    }

    private function parseHeaders($headerString) {
        $headers = explode("\r\n", $headerString);
        foreach($headers as $header) {
            if (strstr($header, ":")) {
                $header = trim($header);
                list($k, $v) = explode(":", $header);
                $headers[$k] = trim($v);
            }
        }
        return $headers;
    }

    private function Ret($resp) {
        $code = $resp->StatusCode;
        $data = null;
        if ($code >= 200 && $code <= 299) {
            if ($resp->ContentLength !== 0 && \UCloud\Header_Get($resp->Header, 'Content-Type') == 'application/json') {
                $data = json_decode($resp->Body, true);
                if ($data === null) {
                    $err = new Error($code, 0, "");
                    return array(null, $err);
                }
            }
        }

        $etag = \UCloud\Header_Get($resp->Header, 'ETag');
        if ($etag != '') {
            $data['ETag'] = $etag;
        }
        if (floor($code/100) == 2) {
            return array($data, null);
        }
        return array($data, \UCloud\ResponseError($resp));
    }

    public function Call($req, $type = Config::HEAD_FIELD_CHECK) {
        list($resp, $err) = $this->RoundTrip($req, $type);
        if ($err !== null) {
            return array(null, $err);
        }
        return $this->Ret($resp);
    }

    public function CallNoRet($req, $type = Config::HEAD_FIELD_CHECK) {
        list($resp, $err) = $this->RoundTrip($req, $type);
        if ($err !== null) {
            return array(null, $err);
        }
        if (floor($resp->StatusCode/100) == 2) {
            return null;
        }
        return \UCloud\ResponseError($resp);
    }

    private function CallWithForm($req, $body, $contentType = 'application/x-www-form-urlencoded') {
        if ($contentType === 'application/x-www-form-urlencoded') {
            if (is_array($req->Params)) {
                $body = http_build_query($req->Params);
            }
        }
        if ($contentType !== 'multipart/form-data') {
            $req->Header['Content-Type'] = $contentType;
        }
        $req->Body = $body;
        list($resp, $err) = $this->RoundTrip($req, Config::HEAD_FIELD_CHECK);
        if ($err !== null) {
            return array(null, $err);
        }
        return $this->Ret($resp);
    }

    public function CallWithMultipartForm($req, $fields, $files) {
        list($contentType, $body) = $this->Build_MultipartForm($fields, $files);
        return $this->CallWithForm($req, $body, $contentType);
    }

    private function Build_MultipartForm($fields, $files) {
        $data = array();
        $boundary = md5(microtime());

        foreach ($fields as $name => $val) {
            array_push($data, '--' . $boundary);
            array_push($data, "Content-Disposition: form-data; name=\"$name\"");
            array_push($data, '');
            array_push($data, $val);
        }

        foreach ($files as $file) {
            array_push($data, '--' . $boundary);
            list($name, $fileName, $fileBody, $mimeType) = $file;
            $mimeType = empty($mimeType) ? 'application/octet-stream' : $mimeType;
            $fileName = $this->EscapeQuotes($fileName);
            array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
            array_push($data, "Content-Type: $mimeType");
            array_push($data, '');
            array_push($data, $fileBody);
        }

        array_push($data, '--' . $boundary . '--');
        array_push($data, '');

        $body = implode("\r\n", $data);
        $contentType = 'multipart/form-data; boundary=' . $boundary;
        return array($contentType, $body);
    }

    private function EscapeQuotes($str) {
        $find = array("\\", "\"");
        $replace = array("\\\\", "\\\"");
        return str_replace($find, $replace, $str);
    }
}
