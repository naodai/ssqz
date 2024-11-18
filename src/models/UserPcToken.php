<?php
/**
 * pc token
 * token 有效期2天
 * 当token创建超过3小时就更新token并更新创建时间以延长登录持续时间，如果用户每天都登录，就一直不会下线。
 */
namespace ssqz\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%user_pc_token}}".
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property int $created_at
 */
class UserPcToken extends UserToken
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_pc_token}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'token'], 'required'],
            [['user_id'], 'integer'],
            [['token'], 'string', 'max' => 100],
            [['user_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'token' => 'Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
