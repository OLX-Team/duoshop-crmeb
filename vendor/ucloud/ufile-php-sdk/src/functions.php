<?php

namespace UCloud;

use UCloud\Http\Error;

if (!defined('UCLOUD_FUNCTIONS_VERSION')) {
    define('UCLOUD_FUNCTIONS_VERSION', Config::SDK_VER);

    function UrlSafe_Encode($data) {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, $data);
    }

    function UrlSafe_Decode($data) {
        $find = array('-', '_');
        $replace = array('+', '/');
        return str_replace($find, $replace, $data);
    }

    function FileHash($file) {
        $f = fopen($file, "r");
        if (!$f) {
            return array(null, new Error(0, -1, "open $file error"));
        }

        $fileSize = filesize($file);
        $buffer   = '';
        $sha      = '';
        $blkcnt   = $fileSize / Config::BLKSIZE;
        if ($fileSize % Config::BLKSIZE) $blkcnt += 1;
        $buffer .= pack("L", $blkcnt);
        if ($fileSize <= Config::BLKSIZE) {
            $content = fread($f, Config::BLKSIZE);
            if (!$content) {
                fclose($f);
                return array("", new Error(0, -1, "read file error"));
            }
            $sha .= sha1($content, TRUE);
        } else {
            for($i=0; $i<$blkcnt; $i+=1) {
                $content = fread($f, Config::BLKSIZE);
                if (!$content) {
                    if (feof($f)) break;
                    fclose($f);
                    return array("", new Error(0, -1, "read file error"));
                }
                $sha .= sha1($content, TRUE);
            }
            $sha = sha1($sha, TRUE);
        }
        $buffer .= $sha;
        $hash = UrlSafe_Encode(base64_encode($buffer));
        fclose($f);
        return array($hash, null);
    }

    function GetFileMimeType($filename) {
        $mimetype = "";
        $ext = "";
        $filename_component = explode(".", $filename);
        if (count($filename_component) >= 2) {
            $ext = "." . $filename_component[count($filename_component)-1];
        }

        if (array_key_exists($ext, Mimetype::map)) {
            $mimetype = Mimetype::map[$ext];
        } else if (function_exists('mime_content_type')) {
            $mimetype = mime_content_type($filename);
        } else if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // 返回 mime 类型
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
        } else {
            return array("application/octet-stream", null);
        }
        return array($mimetype, null);
    }

    function Header_Get($header, $key) {
        $val = @$header[$key];
        if (isset($val)) {
            if (is_array($val)) {
                return $val[0];
            }
            return $val;
        } else {
            return '';
        }
    }

    function ResponseError($resp) {
        $header = $resp->Header;
        $err = new Error($resp->StatusCode, null);

        if ($err->Code > 299) {
            if ($resp->ContentLength !== 0) {
                if (Header_Get($header, 'Content-Type') === 'application/json') {
                    $ret = json_decode($resp->Body, true);
                    $err->ErrRet = $ret['ErrRet'];
                    $err->ErrMsg = $ret['ErrMsg'];
                }
            }
        }
        $err->Reqid = Header_Get($header, 'X-SessionId');
        return $err;
    }

    function CheckConfig($auth, $action) {
        switch ($action) {
            case ActionType::PUTFILE:
            case ActionType::POSTFILE:
            case ActionType::MINIT:
            case ActionType::MUPLOAD:
            case ActionType::MCANCEL:
            case ActionType::MFINISH:
            case ActionType::DELETE:
            case ActionType::UPLOADHIT:
                if ($auth->ProxySuffix == "") {
                        return new Error(400, -1, "no proxy suffix found in config");
                } else if ($auth->PublicKey == "" || strstr($auth->PublicKey, " ") != FALSE) {
                        return new Error(400, -1, "invalid public key found in config");
                } else if ($auth->PrivateKey == "" || strstr($auth->PrivateKey, " ") != FALSE) {
                        return new Error(400, -1, "invalid private key found in config");
                }
                break;
            case ActionType::GETFILE:
                if ($auth->ProxySuffix == "") {
                        return new Error(400, -1, "no proxy suffix found in config");
                }
                break;
            default:
                break;
        }
        return null;
    }
}