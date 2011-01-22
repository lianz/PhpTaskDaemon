<?php
/**
 * @author DirkEngels <d.engels@dirkengels.com>
 * @package Dew
 * @subpackage Dew_Daemon
 */

/**
 * 
 * This example class shows a task running with an interval manager. Every task
 * can adjust the time to wait after finishing all tasks and running again. 
 * This example is called sloppy, because the time between loading a queue and
 * executing its tasks is at a random interval. 
 *
 */
class Dew_Daemon_Task_Example2 extends Dew_Daemon_Task_Abstract implements Dew_Daemon_Task_Interface {

	static protected $_managerType = Dew_Daemon_Manager_Abstract::PROCESS_TYPE_CRON;
	
	/** 
	 * Task input variables: These items must be present in the task
	 * input data in order to execute the task.
	 */
	protected $_inputFields = array('taskId', 'sleepTime');
	
	/**
	 * Loads the task queue: The input for one or more tasks. The input of a 
	 * sleep task must contain the following items:	
	 * - taskId
	 * - sleepTime
	 * 
	 * @see Dew_Daemon_Task::loadTaskQueue()
	 */
	public function loadTasks() {
		$queue = array();
		if (count($queue)==0) {
			for ($i=0; $i<rand(0,20); $i++) {
				array_push($queue, array('taskId' => $i, 'sleepTime' => rand(10000, 500000)));
			}
			// Inform the manager about the amount of tasks loaded into the queue
			$this->updateMemoryQueue(count($queue));
		}

		return $queue;
	}
	
	/**
	 * Execute the task: Sleeps for a little while.
	 * 
	 * @see Dew_Daemon_Task::executeTask()
	 */
	public function executeTask() {
		$inputData = $this->getTaskInput();
		
		// Sleep
		$data = substr(md5(uniqid()), 0,10);
		for ($i=1; $i<5; $i++) {
			usleep($inputData['sleepTime']);
			$this->updateMemoryTask(($i*20), 'Task data: ' . $data);
		}

		// Override sleeping time
		$this->_sleepTime = rand(1,5);
		return $this->_sleepTime;
	}
	
}