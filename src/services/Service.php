<?php

namespace ssqz\services;


use yii\helpers\ArrayHelper;
use yii\httpclient\Client;
use yii\web\ServerErrorHttpException;

class Service
{
    public static function api($method, $baseUrl, $url, $data = [], $type = 'json')
    {
        $method = strtoupper($method);
        $type = strtoupper($type);

        $client = new Client(['baseUrl' => $baseUrl]);
        $client = $client->createRequest();
        $client->setMethod($method);
        if ($type == 'JSON' && $method == 'POST') {
            $client->setFormat(Client::FORMAT_JSON);
        } elseif ($type == 'JSON' && $method == 'GET') {
            $client->setFormat(Client::FORMAT_RAW_URLENCODED);
        }
        $client->setHeaders(['Content-Type' => 'application/json']);

        $apiParams = [
            'client' => \Yii::$app->request->get('client', 'pc'),
            'origin' => \Yii::$app->id,
            'referer' => 'server',
        ];
        $signData = $apiParams;
        if ($method == 'GET') {
            $signData = array_merge($data, $apiParams);
        }
        ksort($signData);
        if (strpos($url, '?') === false) {
            $url .= '?' . http_build_query($signData,'','&',PHP_QUERY_RFC3986);
        } else {
            $url .= '&' . http_build_query($signData,'','&',PHP_QUERY_RFC3986);
        }
        $signResult = self::sign($baseUrl.'/'.$url);
        $getParams = array_merge($apiParams, $signResult);

        if ($method == 'POST' && !empty($data)) {
            $client->setData($data);
        }

        $url .= '&_c='.$signResult['_c'].'&_d='.$signResult['_d'].'&_s='.$signResult['_s'].'&_t='.$signResult['_t'];

        $client->setUrl($url);
        $res = $client->send();
        if ($res->isOk) {
            if ($res->data) {
                if(array_key_exists('data', $res->data)){
                    return $res->data['data'];
                }
                return $res->data;
            }
        } else {
//            var_dump($signUrl);
//            var_dump($url);
//            var_dump($data);
//            var_dump($res->statusCode);
//            var_dump($res->data);
//            exit;
            throw new ServerErrorHttpException('HTTP API SEND ERROR', 100000, null);
        }
        return false;
    }

    private static function sign($url)
    {
        /*
签署步骤
从服务端得到客户端ID和秘钥
准备好_c、_d、_s、_t参数四个
_c：客户端ID
_d：当前时间间隔（秒）
_s：计算数值，取当前定时器（秒）后6位 * 12345.6789，再进行进一取整
_t：Token，将上面三个参数粘贴到URL参数最后进行MD5加密然后粘贴上面三个参数的值，然后粘贴客户端秘钥，最后进行SHA1加密
将上面的四个参数拼接到需要访问的 URL 参数最后进行请求
         */
        $c = 'ssqz'; // 客户端ID
        $d = time(); // 时间戳（秒）
        $s = ceil(substr($d, -6) * 12345.6789); // 计算数值

//        $url = 'http://xxx.com/index/index?page=1&per-page=10';
        $url = explode('?', $url);
        $params = trim($url[1] . '&_c=' . $c . '&_d=' . $d . '&_s=' . $s, '&');
        $newUrl = $url[0] . '?' . $params;

        $clientSecrets = ArrayHelper::index(\Yii::$app->params['signature']['clientSecrets'], 'id');
        $clientSecret = $clientSecrets[$c];
        $t = sha1(md5($newUrl) . $c . $d . $s . $clientSecret['secret']); // Token

        $newUrl .= '&_t=' . $t;
        return [
            '_c' => $c,
            '_d' => $d,
            '_s' => $s,
            '_t' => $t
        ];
    }
}