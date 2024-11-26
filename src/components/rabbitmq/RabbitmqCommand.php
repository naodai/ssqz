<?php
/**
 * 重写Rabbitmq Command
 * 支持指定exchange queue
 */
namespace ssqz\components\rabbitmq;

class RabbitmqCommand extends \yii\queue\amqp_interop\Command
{
    /**
     * @param $channelName
     * @return void
     */
    public function actionListen($channelName = 'default')
    {
        $queue = $this->queue;
        $queue->exchangeName = $channelName;
        $queue->queueName = $channelName;
        parent::actionListen();
    }
}