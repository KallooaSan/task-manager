<?php

namespace Alchemy\Test\TaskManager\Event\TaskManagerSubscriber;

use Alchemy\TaskManager\Event\StateFormater;
use Alchemy\TaskManager\Event\TaskManagerSubscriber\StatusOnTickPublisherSubscriber;
use Alchemy\TaskManager\TaskManager;

class StatusOnTickPublisherSubscriberTest extends SubscriberTestCase
{
    public function testSocketIsBoundOnStart()
    {
        $socket = $this->createSocketMock();
        $socket->expects($this->once())
            ->method('bind');

        $subscriber = $this->getSubscriber($socket);
        $subscriber->onManagerStart($this->getEvent());
    }

    public function testSocketIsUnboundOnStop()
    {
        $socket = $this->createSocketMock();
        $socket->expects($this->once())
            ->method('unbind');

        $subscriber = $this->getSubscriber($socket);
        $subscriber->onManagerStop($this->getEvent());
    }

    public function testDataIsSentOnTickIfNotSame()
    {
        $result = array('ping' => 'pong');
        $result2 = array('ping' => 'pouf');

        $socket = $this->createSocketMock(array('send'));
        $socket->expects($this->exactly(2))
            ->method('send');

        $formater = $this->getMockBuilder('Alchemy\TaskManager\Event\StateFormater')
            ->disableOriginalConstructor()
            ->getMock();
        $formater->expects($this->at(0))
                ->method('toArray')
                ->with(array('array', 'of', 'managed-processes'))
                ->will($this->returnValue($result));
        $formater->expects($this->at(1))
                ->method('toArray')
                ->with(array('array', 'of', 'managed-processes'))
                ->will($this->returnValue($result2));

        $processManager = $this->getMockBuilder('Neutron\ProcessManager\ProcessManager')
                ->disableOriginalConstructor()
                ->getMock();
        $processManager->expects($this->exactly(2))
                ->method('getManagedProcesses')
                ->will($this->returnValue(array('array', 'of', 'managed-processes')));

        $manager = $this->getMockBuilder('Alchemy\TaskManager\TaskManager')
                ->disableOriginalConstructor()
                ->getMock();
        $manager->expects($this->exactly(2))
                ->method('getProcessManager')
                ->will($this->returnValue($processManager));

        $event = $this->getEvent();
        $event->expects($this->exactly(2))
                ->method('getManager')
                ->will($this->returnValue($manager));

        $subscriber = $this->getSubscriber($socket, $formater);
        $subscriber->onManagerTick($event);
        $subscriber->onManagerTick($event);
    }

    public function testSameDataIsBypassedOnTick()
    {
        $result = array('ping' => 'pong');

        $socket = $this->createSocketMock(array('send'));
        $socket->expects($this->once())
            ->method('send')
            ->with(json_encode($result));

        $formater = $this->getMockBuilder('Alchemy\TaskManager\Event\StateFormater')
            ->disableOriginalConstructor()
            ->getMock();
        $formater->expects($this->exactly(5))
                ->method('toArray')
                ->with(array('array', 'of', 'managed-processes'))
                ->will($this->returnValue($result));

        $processManager = $this->getMockBuilder('Neutron\ProcessManager\ProcessManager')
                ->disableOriginalConstructor()
                ->getMock();
        $processManager->expects($this->exactly(5))
                ->method('getManagedProcesses')
                ->will($this->returnValue(array('array', 'of', 'managed-processes')));

        $manager = $this->getMockBuilder('Alchemy\TaskManager\TaskManager')
                ->disableOriginalConstructor()
                ->getMock();
        $manager->expects($this->exactly(5))
                ->method('getProcessManager')
                ->will($this->returnValue($processManager));

        $event = $this->getEvent();
        $event->expects($this->exactly(5))
                ->method('getManager')
                ->will($this->returnValue($manager));

        $subscriber = $this->getSubscriber($socket, $formater);
        $subscriber->onManagerTick($event);
        $subscriber->onManagerTick($event);
        $subscriber->onManagerTick($event);
        $subscriber->onManagerTick($event);
        $subscriber->onManagerTick($event);
    }

    public function testCreate()
    {
    }

    protected function getSubscriber($socket = null, $formater = null)
    {
        if (null === $socket) {
            $socket = $this->createSocketMock();
        }
        if (null === $formater) {
            $formater = new StateFormater();
        }

        return new StatusOnTickPublisherSubscriber($socket, $formater);
    }

    private function getEvent()
    {
        return $this->getMockBuilder('Alchemy\TaskManager\Event\TaskManagerEvent')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createSocketMock($methods = array())
    {
        $builder = $this->getMockBuilder('Alchemy\TaskManager\ZMQSocket')
            ->disableOriginalConstructor();

        if (!empty($methods)) {
            $builder->setMethods($methods);
        }

        return $builder->getMock();
    }
}
