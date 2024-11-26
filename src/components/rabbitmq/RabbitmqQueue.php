<?php
/**
 * 重写Rabbitmq Queue
 * 支持指定exchange queue
 */
namespace ssqz\components\rabbitmq;

use ssqz\components\rabbitmq\RabbitmqCommand;

class RabbitmqQueue extends \yii\queue\amqp_interop\Queue
{

    /* @var $commandClass RabbitmqCommand */
    public $commandClass = RabbitmqCommand::class;

    /**
     * 设置exchange queue
     * @param $channel
     * @return $this
     */
    public function setChannel($channel)
    {
        $this->exchangeName = $channel;
        $this->queueName = $channel;
        return $this;
    }
}