<?php

namespace ssqz\controllers;

use xihrni\yii2\behaviors\SignatureBehavior;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\web\Response;

class BaseController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $behaviors = parent::behaviors();

        //接口签名
        $behaviors['signature'] = [
            'class' => SignatureBehavior::className(),
            //开关，开发环境可以设置false，不进行签名校验
            'switchOn' => Yii::$app->params['signature']['switchOn'],
            //过滤
            'optional' => Yii::$app->params['signature']['optional'],
            //是否提示具体错误
            'isHint' => Yii::$app->params['signature']['isHint'],
            //客户端密钥集合
            'clientSecrets' => Yii::$app->params['signature']['clientSecrets'],
        ];


        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;

        return $behaviors;
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        /* @var $this ->parent Controller */
        if (parent::beforeAction($action)) {
            $origin = ArrayHelper::getValue($_SERVER, 'HTTP_ORIGIN');

            if ($origin) {
                $scheme = parse_url($origin, PHP_URL_SCHEME);
                $host = parse_url($origin, PHP_URL_HOST);
                $port = parse_url($origin, PHP_URL_PORT);
                if (substr($host, -16) == 'shushuqiuzhi.com'
                ) {
                    $url = "{$scheme}://{$host}";
                    if ($port) {
                        $url .= ":{$port}";
                    }
                    $headers = 'Origin, X-Requested-With, Content-Type,Token,Timestamp,Authorization, ' . join(', ', array_keys(Yii::$app->request->headers->toArray()));
                    $header = Yii::$app->response->headers;
                    $header->add('Access-Control-Allow-Origin', $url);
                    $header->add('Access-Control-Allow-Headers', $headers);
                    $header->add('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE ,PUT');
                    $header->add('Access-Control-Allow-Credentials', 'true');
                    $header->add('Access-Control-Expose-Headers', join(',', ['X-Pagination-Current-Page', 'X-Pagination-Page-Count', 'X-Pagination-Per-Page', 'X-Pagination-Total-Count',]));
                }
            }
            if (Yii::$app->request->method == 'OPTIONS') {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }
}
