<?php

namespace ssqz\controllers;

use xihrni\yii2\behaviors\SignatureBehavior;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\web\Response;

class MustLoginedBaseController extends BaseController
{
    public function beforeAction($action)
    {
        // Auth 校验
        $config = [
            'authMethods' => [
                //HttpBasicAuth::className(),
                HttpBearerAuth::className(),
                QueryParamAuth::className(),
            ],
            'optional' => [
                'phone-password-login',
                'send-phone-code',
                'phone-code-login',
                'captcha',
            ]
        ];
        $compositionAuth = new CompositeAuth($config);
        if (parent::beforeAction($action)) {
            return $compositionAuth->beforeAction($action);
        } else {
            return false;
        }
    }
}
