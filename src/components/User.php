<?php

namespace ssqz\components;


use ssqz\models\UserToken;
use ssqz\services\Service;

class User extends \yii\web\User
{
    public function init()
    {
        parent::init();

        $authHeader = UserToken::getHeaderToken();
        if ($authHeader) {
            /* @var $identity \ssqz\models\User */
            $identity = $this->identityClass;
            $this->setIdentity($identity::findIdentityByAccessToken($authHeader, false));
        }
    }

    public function afterLogin($identity, $cookieBased, $duration)
    {
        //智慧鼠ai优化次数添加
        Service::api('POST', \Yii::getAlias('@jianliApi'), 'ai-optimize-internal/add', ['user_id' => $identity->getId()],'json');
        parent::afterLogin($identity, $cookieBased, $duration);
    }
}