<?php
namespace Naodai\Ssqz\Components\Rabbitmq;

class RabbitmqCommand extends \yii\queue\amqp_interop\Command
{
    public function actionListen($queueName = 'default', $exchangeName = 'default')
    {
        $queue = $this->queue;
        $queue->queueName = $queueName;
        $queue->exchangeName = $exchangeName;
        parent::actionListen();
    }

}