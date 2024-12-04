<?php

namespace ssqz\components;


use ssqz\models\UserToken;

class User extends \yii\web\User
{
    public function init()
    {
        parent::init();

        $authHeader = UserToken::getHeaderToken();
        if ($authHeader) {
            $identity = $this->identityClass;
            $this->setIdentity($identity::findIdentityByAccessToken($authHeader, false));
        }
    }
}