<?php

namespace ssqz\models;

use Yii;

class UserToken extends \yii\db\ActiveRecord
{

    public static function validateToken($token, $refresh = false)
    {
        $tokens = UserToken::getTokenInfo($token);

        if (!$tokens) {
            return false;
        }
        $userId = $tokens['userId'];
        if(!$userId) {
            return false;
        }
        $tokenModel = null;
        $client = Yii::$app->request->get('client');
        switch ($client) {
            case "pc":
                $tokenModel = UserPcToken::findOne(['user_id' => $userId]);
                break;
            default:
                break;
        }
        if (!$tokenModel) {
            return false;
        }
        if ($tokenModel->token != $token) {
            return false;
        }
        $time = time();
        if ($time - $tokenModel->updated_at > 2 * 24 * 60 * 60) {
            return false;
        }
        //更新
        if ($refresh) {
            if ($time - $tokenModel->updated_at > 3 * 60 * 60) {
                self::generateToken();
            }
        }
        return $userId;
    }

    public static function generateToken()
    {
        $userId = Yii::$app->user->id;
        $tokenModel = null;
        $client = Yii::$app->request->get('client');
        switch ($client) {
            case "pc":
                $tokenModel = UserPcToken::findOne(['user_id' => $userId]);
                break;
                default:
                    break;
        }
        if (!$tokenModel) {
            $tokenModel = new UserPcToken();
            $tokenModel->user_id = $userId;
        }
        $tokenModel->token = UserToken::generateTokenPrefix($userId) . "_" . Yii::$app->security->generateRandomString();
        if (!$tokenModel->save()) {
            var_dump($tokenModel->getErrors());
            exit;
        }
        $headers = Yii::$app->response->headers;

        // 增加一个 Pragma 头，已存在的Pragma 头不会被覆盖。
        $headers->add('token', $tokenModel->token);
        return $tokenModel->token;
    }

    public static function getTokenInfo($token)
    {
        if (strpos($token, '_') === false) {
            return false;
        }
        $num = strpos($token, '_');
//        echo $num;
//        echo "\r\n";
        $userId = substr($token, 0, $num);
        $userId = self::decrypt($userId);
        $token = substr($token, $num + 1);
//        echo $userId;
//        echo "\r\n";
//        echo $token;
        return [
            'userId' => $userId,
            'token' => $token
        ];
    }

    public static function generateTokenPrefix($userId)
    {
        return self::encrypt($userId);
    }

    //设置AES秘钥
    private static $aes_key = 'bUYJ3nTV6VBasdJF'; //此处填写前后端共同约定的秘钥

    /**
     * 加密
     * @param string $str 要加密的数据
     * @return bool|string   加密后的数据
     */
    static public function encrypt($str)
    {

        $data = openssl_encrypt($str, 'AES-128-ECB', self::$aes_key, OPENSSL_RAW_DATA);
        $data = base64_encode($data);

        return $data;
    }

    /**
     * 解密
     * @param string $str 要解密的数据
     * @return string        解密后的数据
     */
    static public function decrypt($str)
    {

        $decrypted = openssl_decrypt(base64_decode($str), 'AES-128-ECB', self::$aes_key, OPENSSL_RAW_DATA);
        return $decrypted;
    }
}
