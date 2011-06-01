<?php

/**
 * @package PhpTaskDaemon
 * @subpackage Task\Executor
 * @copyright Copyright (C) 2011 Dirk Engels Websolutions. All rights reserved.
 * @author Dirk Engels <d.engels@dirkengels.com>
 * @license https://github.com/DirkEngels/PhpTaskDaemon/blob/master/doc/LICENSE
 *
 * @group PhpTaskDaemon
 * @group PhpTaskDaemon-Task
 * @group PhpTaskDaemon-Task-Manager
 */

namespace PhpTaskDaemon\Task\Manager;

class BaseClassTest extends \PHPUnit_Framework_Testcase {
	protected $_manager;
	protected $_executor;
	protected $_queue;
	
	protected function setUp() {
		$this->_executor = new \PhpTaskDaemon\Task\Executor\BaseClass();
		$this->_queue = new \PhpTaskDaemon\Task\Queue\BaseClass();
		$this->_manager = new \PhpTaskDaemon\Task\Manager\BaseClass($this->_executor);
	}
	protected function tearDown() {
		unset($this->_manager);
		unset($this->_executor);
}
	
	public function testConstructor() {
		$this->assertInstanceOf('\PhpTaskDaemon\Task\Executor\AbstractClass', $this->_manager->getExecutor());
		$this->assertEquals($this->_executor, $this->_manager->getExecutor());
		$this->assertInstanceOf('\PhpTaskDaemon\Task\Queue\AbstractClass', $this->_manager->getQueue());
		$this->assertEquals($this->_queue, $this->_manager->getQueue());
	}
	public function testInitNoArguments() {
		$this->_manager->init();
		$this->assertInstanceOf('\PhpTaskDaemon\Daemon\Pid\Manager', $this->_manager->getPidManager());
		$this->assertEquals(getmypid(), $this->_manager->getPidManager()->getCurrent());
		$this->assertNull($this->_manager->getPidManager()->getParent());
	}
	public function testInitWithParentPid() {
		$this->_manager->init(1234);
		$this->assertInstanceOf('\PhpTaskDaemon\Daemon\Pid\Manager', $this->_manager->getPidManager());
		$this->assertEquals(getmypid(), $this->_manager->getPidManager()->getCurrent());
		$this->assertEquals(1234, $this->_manager->getPidManager()->getParent());
	}
	public function testSetLog() {
		$log = new \Zend_Log();
		$this->assertNull($this->_manager->getLog());
		$this->_manager->setLog($log);
		$this->assertEquals($log, $this->_manager->getLog());
	}
	public function testSetPidManager() {
		$pidManager = new \PhpTaskDaemon\Daemon\Pid\Manager();
		$this->assertNull($this->_manager->getPidManager());
		$this->_manager->setPidManager($pidManager);
		$this->assertEquals($pidManager, $this->_manager->getPidManager());
	}
	public function testSetQueue() {
		$this->assertInstanceOf('\PhpTaskDaemon\Task\Queue\AbstractClass', $this->_manager->getQueue());
		$this->_manager->setQueue($this->_queue);
		$this->assertEquals($this->_queue, $this->_manager->getQueue());
	}
	public function testSetExecutor() {
		$this->assertInstanceOf('\PhpTaskDaemon\Task\Executor\AbstractClass', $this->_manager->getExecutor());
		$this->_manager->setExecutor($this->_executor);
		$this->assertEquals($this->_executor, $this->_manager->getExecutor());
	}
	public function testSetExecutorIncorrectExecutor() {
		$this->_manager->setExecutor('no executor object');
		$this->assertInstanceOf('\PhpTaskDaemon\Task\Executor\AbstractClass', $this->_manager->getExecutor());
		$this->assertEquals($this->_executor, $this->_manager->getExecutor());
	}
	public function testLog() {
		$this->assertFalse($this->_manager->log('test log message', \Zend_Log::INFO));
		$writer = new \Zend_Log_Writer_Null();
		$log = new \Zend_Log($writer);
		$this->_manager->setLog($log);
		$this->assertNull($this->_manager->log('test log message', \Zend_Log::INFO));
	}
}
