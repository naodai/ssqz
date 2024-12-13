<?php

namespace ssqz\models;

use Yii;
use yii\filters\auth\HttpBearerAuth;

class UserToken extends \yii\db\ActiveRecord
{
    //设置连接符
//    static private $_needle = Yii::$app->params['userToken']['needle'];

    //设置AES秘钥
//    private static $aes_key = Yii::$app->params['userToken']['aesKey']; //此处填写前后端共同约定的秘钥

    /**
     * @param $token
     * @param $refresh
     * @return false|mixed
     * @throws \yii\base\Exception
     */
    public static function validateToken($token, $refresh = null)
    {
        $userId = UserToken::getUserIdByToken($token);
        if (!$userId) {
            Yii::warning("Error UserId:" . $userId, __METHOD__);
            return false;
        }
        $tokenModel = null;
        $client = Yii::$app->request->get('client');
        switch ($client) {
            case "pc":
                $tokenModel = UserPcToken::findOneByUserId($userId);
                break;
            default:
                Yii::warning("Error Client:" . $client, __METHOD__);
                return false;
        }
        if (!$tokenModel) {
            Yii::warning("Error TokenModel:" . $tokenModel, __METHOD__);
            return false;
        }
        if ($tokenModel->token != $token) {
            Yii::warning("Error Token:" . $tokenModel->token, __METHOD__);
            return false;
        }
        $time = time();
        $timeCha = $time - $tokenModel->updated_at;
        if ($timeCha > 2 * 24 * 60 * 60) {
            Yii::warning("Error Time99:" . $time . " " . $tokenModel->updated_at . " " . $timeCha, __METHOD__);

            //throw new Exception("Token Expired",403);
            return false;
        }
        /*
        //更新
        if ($refresh !== false) {
            Yii::info("Refresh Token UserId:" . $userId, __METHOD__);
//            if ($timeCha > 3 * 60 * 60) {
            Yii::info($userId, __METHOD__);
            self::generateToken();
//            }
        }*/
        return $userId;
    }

    public static function refreshToken($userId)
    {
        $tokenModel = null;
        $client = Yii::$app->request->get('client');
        switch ($client) {
            case "pc":
                $tokenModel = UserPcToken::findOneByUserId($userId);
                break;
            default:
                Yii::warning("Error Client:" . $client, __METHOD__);
                return false;
        }
        if (!$tokenModel) {
            Yii::warning("Error TokenModel:" . $tokenModel, __METHOD__);
            return false;
        }

//        $time = time();
//        $timeCha = $time - $tokenModel->updated_at;
//        if ($timeCha > 2 * 24 * 60 * 60) {
//            Yii::warning("Error Time99:" . $time . " " . $tokenModel->updated_at . " " . $timeCha, __METHOD__);
//
//            //throw new Exception("Token Expired",403);
//            return false;
//        }
        //更新
        Yii::info("Refresh Token UserId:" . $userId, __METHOD__);
        Yii::info($userId, __METHOD__);
        $newToken = self::generateToken();
        return $newToken;
    }

    /**
     * @param $token
     * @return array|false
     */
    public static function getUserIdByToken($token)
    {
        if (strpos($token, Yii::$app->params['userToken']['needle']) === false) {
            Yii::warning("Error Token " . Yii::$app->params['userToken']['needle'] . ":" . $token, __METHOD__);
            return false;
        }
        $num = strpos($token, Yii::$app->params['userToken']['needle']);
        $userIdEncrypt = substr($token, 0, $num);
        return self::getDecrypt($userIdEncrypt);
    }

    /**
     * @param $encrypt
     * @return string
     */
    public static function getDecrypt($encrypt)
    {
        return self::decrypt($encrypt);
    }

    /**
     * 解密
     * @param string $str 要解密的数据
     * @return string        解密后的数据
     */
    static public function decrypt($str)
    {

        $decrypted = openssl_decrypt(base64_decode($str), 'AES-128-ECB', Yii::$app->params['userToken']['aesKey'], OPENSSL_RAW_DATA);
        return $decrypted;
    }

    /**
     * @return false|mixed
     * @throws \yii\base\Exception
     */
    public static function generateToken()
    {
        $userId = Yii::$app->user->id;
        $token = self::generateTokenPrefix($userId) . Yii::$app->params['userToken']['needle'] . Yii::$app->security->generateRandomString();
        $client = Yii::$app->request->get('client');
        $result = false;
        switch ($client) {
            case "pc":
                $result = UserPcToken::createUpdateToken($userId, $token);
                break;
            default:
                Yii::info("Error Client:" . $client, __METHOD__);
                return false;
        }
        if (!$result) {
            return false;
        }

        $headers = Yii::$app->response->headers;
        // 增加一个 Token 头，已存在的头不会被覆盖。
        $headers->add('Token', $result);
        return $result;
    }

    /**
     * @param $userId
     * @return bool|string
     */
    public static function generateTokenPrefix($userId)
    {
        return self::encrypt($userId);
    }

    /**
     * 加密
     * @param string $str 要加密的数据
     * @return bool|string   加密后的数据
     */
    static public function encrypt($str)
    {
        $data = openssl_encrypt($str, 'AES-128-ECB', Yii::$app->params['userToken']['aesKey'], OPENSSL_RAW_DATA);
        $data = base64_encode($data);

        return $data;
    }

    /**
     * @return false|string
     */
    public static function getHeaderToken()
    {
        $auth = new HttpBearerAuth();
        $authHeader = Yii::$app->request->getHeaders()->get($auth->header,'');
        if (preg_match($auth->pattern, $authHeader, $matches)) {
            return $matches[1];
        }
        return false;
    }
}
