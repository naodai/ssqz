<?php

namespace ssqz\components;


use ssqz\models\UserToken;
use yii\filters\auth\HttpBearerAuth;

class User extends \yii\web\User
{
    public function init()
    {
        parent::init();

        $authHeader = UserToken::getHeaderToken();
        if ($authHeader) {
            $identity = $this->identityClass;
            $this->setIdentity($identity::findIdentityByAccessToken($authHeader, false));
//            var_dump($this->isGuest);
//            var_dump($this->id);
//            if(!$this->isGuest) {
//                var_dump($this->identity->toArray());
//                var_dump($this->getIdentity(false)->phone);
//            }
        }
    }
}