<?php

namespace Naodai\Ssqz\Components\Rabbitmq;

class RabbitmqQueue extends \yii\queue\amqp_interop\Queue
{
    public $commandClass = RabbitmqCommand::class;
}