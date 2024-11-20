<?php

namespace ssqz\controllers;

use xihrni\yii2\behaviors\SignatureBehavior;
use Yii;
use yii\rest\Controller;
use yii\web\Response;

class BaseController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
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
}
