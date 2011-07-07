<?php
/**
 * @package PhpTaskDaemon
 * @subpackage Daemon
 * @copyright Copyright (C) 2011 Dirk Engels Websolutions. All rights reserved.
 * @author Dirk Engels <d.engels@dirkengels.com>
 * @license https://github.com/DirkEngels/PhpTaskDaemon/blob/master/doc/LICENSE
 */

namespace PhpTaskDaemon\Daemon;

/**
* The main Daemon class is responsible for starting, stopping and monitoring
* the daemon. It accepts command line arguments to set daemon daemon options.
* The start method creates an instance of Dew_Daemon_Daemon and contains the 
* methods for setup a logger, read the config, daemonize the process and set 
* the uid/gui of the process. After that the daemon instance starts all the 
* managers.
*/
class Console {
	
    /**
     * Configuration Object
     * @var Zend_Config
     */
    protected $_config;
    
	/**
	 * Console options object
	 * @var Zend_Console_Getopt
	 */
	protected $_consoleOpts;
	
	/**
	 * 
	 * Daemon run object
	 * @var Daemon
	 */
	protected $_instance;

	/**
	 * 
	 * Daemon constructor method
	 * @param \Zend_Console_Getopt $consoleOpts
	 */
	public function __construct(Daemon $instance = null) {
		// Initialize command line arguments
		$this->setDaemon($instance);
		$this->setConsoleOpts();
	}
	
	/**
	 * 
	 * Returns an object containing the console arguments.
	 * @return Zend_Console_Getopt
	 */
	public function getConsoleOpts() {
		// Initialize default console options
		if (is_null($this->_consoleOpts)) {
			$this->_consoleOpts = new \Zend_Console_Getopt(
				array(
					'config-file|c-s'	=> 'Configuration file (defaults: /etc/{name}.conf, {cwd}/{name}.conf)',
					'log-file|l-s'	    => 'Log file (defaults /var/log/{name}.log, {cwd}/{name}.log)',
//                    'tmp-dir|td-s'      => 'Tmp directory (defaults /tmp/,',
//				    'daemonize|d'	    => 'Run in Daemon mode (default) (fork to background)',
					'action|a=s'	    => 'Action (default: start) (options: start, stop, restart, status, monitor)',
                    'list-tasks|lt'     => 'List tasks',
//                    'settings|s'        => 'Display tasks settings',
//				    'task|t=s'	 	    => 'Run single task',
				    'verbose|v'		    => 'Verbose',
					'help|h'	      	=> 'Show help message (this message)',
				)
			);
		}
		return $this->_consoleOpts;
	}
	
	/**
	 * 
	 * Sets new console arguments
	 * @param Zend_Console_Getopt $consoleOpts
	 * @return $this
	 */
	public function setConsoleOpts(Zend_Console_Getopt $consoleOpts = null) {
		if ($consoleOpts === null) {
			$consoleOpts = $this->getConsoleOpts();
		}
		
		// Parse Options
		try {
			$consoleOpts->parse();
		} catch (Zend_Console_Getopt_Exception $e) {
			echo $e->getUsageMessage();
			exit;
		}
		$this->_consoleOpts = $consoleOpts;

		return $this;
	}
	
	/**
	 * 
	 * Returns the daemon daemon object
	 * @return Daemon
	 */
	public function getDaemon() {
		if ($this->_instance === null) {
			$this->_instance = new Instance();
		}
		return $this->_instance;
	}
	
	/**
	 * 
	 * Sets a daemon daemon object
	 * @param Daemon $instance
	 * @return $this
	 */
	public function setDaemon($instance) {
		$this->_instance = $instance;
		return $this;
	}
	
	/**
	 * 
	 * Gets a config object
	 * @return \Zend_Config
	 */
	public function getConfig() {
		if ($this->_config === null) {
			$this->_initConfig(array());
		}
		return $this->_config;
	}

	/**
	 * 
	 * Sets a config object
	 * @param \Zend_Config $config
	 * @return $this
	 */
	public function setConfig($config) {
		$this->_config = $config;
	}

	protected function _initConfig() {
        // Prepare configuration files
        $configFiles = array();
        if ($this->_consoleOpts->getOption('config-file')!='') {
            $configArguments = explode(',', $this->_consoleOpts->getOption('config-file'));
            foreach ($configArguments as $configArgument) {
                if (!strstr($configArgument, '/')) {
                    $configArgument = \APPLICATION_PATH . '/' . $configArgument;
                }
                array_push($configFiles, $configArgument);
            } 
        }

        // Initiate config
        $config = \PhpTaskDaemon\Daemon\Config::get($configFiles);
	}

	
	protected function _initLogVerbose() {
       // Log Verbose Output
        if ($this->_consoleOpts->getOption('verbose')) {
			$writerVerbose = new \Zend_Log_Writer_Stream('php://output');
	        \PhpTaskDaemon\Daemon\Logger::get()->addWriter($writerVerbose);
	        \PhpTaskDaemon\Daemon\Logger::get()->log('Adding log writer: verbose', \Zend_Log::DEBUG);
        }
	}
	
	protected function _initLogFile() {
		$logFile = ($this->_consoleOpts->getOption('log-file'))
            ? getcwd() . '/' . $this->_consoleOpts->getOption('log-file')
            : Config::get()->getOptionByDaemonConfig('logfile');

            echo $logFile;
            exit;
        // Create logfile if not exists
		if (!file_exists($logFile)) {
			try {
                touch($logFile);
			} catch (\Exception $e) {
				throw new \Exception('Cannot create log file');
			}
		}
		
		// Adding logfile
        $writerFile = new \Zend_Log_Writer_Stream($logFile);
        \PhpTaskDaemon\Daemon\Logger::get()->addWriter($writerFile);
        \PhpTaskDaemon\Daemon\Logger::get()->log('Adding log writer: ' . $logFile, \Zend_Log::DEBUG);
	}


	/**
	 * 
	 * Reads the command line arguments and invokes the selected action.
	 */
	public function run() {
		try {
			// Set verbose mode (--verbose)
            $this->_initLogVerbose();

	        // Initialize Configuration
	        $this->_initConfig();
            
	        // Add Log Files
	        $this->_initLogFile();
	        
	        // List Tasks & exit (--list-tasks)
	        $this->listTasks();

            // Display Settings & exit (--settings)
            $this->displaySettings();

            // Check action, otherwise display help
            $action = $this->_consoleOpts->getOption('action');
	        $allActions = array('start', 'stop', 'restart', 'status', 'monitor');
			if (in_array($action, $allActions))  {
	            // Perform action
	            $this->$action();
				exit;
			}
            $this->help();
			
		} catch (\Exception $e) {
			Logger::get()->log('FATAL EXCEPTION: ' . $e->getMessage(), \Zend_Log::CRIT);
		}
        exit;
	}

	/**
	 * 
	 * Lists the current loaded tasks. 
	 */
    public function listTasks() {
        if ($this->_consoleOpts->getOption('list-tasks')) {
	        $tasks = array_merge(
	            $this->scanDirectoryForTasks(APPLICATION_PATH . '/Tasks/'),
	            $this->scanConfigForTasks(
	                $this->_consoleOpts->getOption('config-file')
	            )
	        );
	    	exit;
        }
    }

    public function displaySettings() {
        $tasks = array_merge(
            $this->scanDirectoryForTasks(APPLICATION_PATH . '/Tasks/'),
            $this->scanConfigForTasks(
                $this->_consoleOpts->getOption('config-file')
            )
        );
    	echo "Tasks\n";
        echo "=====\n\n";

        echo "Examples\\Minimal\n";
        echo "-----------------\n";
        echo "\tProcess:\t\tSame\t\t\t(default)\n";
        echo "\tTrigger:\t\tInterval\t\t(default)\n";
            echo "\t- sleepTime:\t\t3\t\t\t(default)\n";
        echo "\tStatus:\t\t\tNone\t\t\t(default)\n";
        echo "\tStatistics:\t\tNone\t\t\t(default)\n";
        echo "\tLogger:\t\t\tNone\t\t\t(default)\n";
        echo "\n";

        echo "Examples\\Parallel\n";
        echo "-----------------\n";
        echo "\tProcess:\t\tParallel\t\t(config)\n";
            echo "\t- maxProcesses:\t\t3\t\t\t(default)\n";
        echo "\tTrigger:\t\tCron\t\t\t(default)\n";
            echo "\t- cronTime:\t\t*/15 * * * *\t\t(default)\n";
        echo "\tStatus:\t\t\tNone\t\t\t(default)\n";
        echo "\tStatistics:\t\tNone\t\t\t(default)\n";
        echo "\tLogger:\t\t\tDataBase\t\t(default)\n";
        echo "\n";
        
        foreach($tasks as $nr => $taskName) {
            echo "- " . $taskName . "\n";
        }
        echo "\n";
        exit;
    	
    }

	/**
	 * 
	 * Scans a directory for task managers and returns the number of loaded
	 * tasks.
	 * 
	 * @param string $dir
	 * @return integer
	 */
	public function scanTasksInDirs($dir, $group = null) {
		if (!is_dir($dir . '/' . $group)) {
			throw new \Exception('Directory does not exists');
		}

		$items = scandir($dir . '/' . $group);
		$managers = array();
		$defaultClasses = array('Executor', 'Queue', 'Manager', 'Job');
		foreach($items as $item) {
			if ($item== '.' || $item == '..') { continue; }
			$base = (is_null($group)) ? $item : $group . '/'. $item;
			if (preg_match('/Manager.php$/', $base)) {
				// Try manager file
				echo "Checking manager file: /Tasks/" . $base . "\n";
				if (class_exists(preg_replace('#/#', '\\', 'Tasks/' . substr($base, 0, -4)))) {
					array_push($managers, substr($base, 0, -12));
				}
			} elseif (is_dir($dir . '/' . $base)) {
				// Load recursively
				$managers = array_merge(
					$managers, 
					$this->scanDirectoryForTasks($dir, $base)
				);
			}
		}
		return $managers;
	}
	
	public function scanTasksInConfig($configFile) {
		return array();
	}


	/**
     * Loads a task by name. A task should at least contain an executor object.
     * The manager, job, queue, process, trigger, status and statistics objects
     * are automatically detected. For each object the method checks if the 
     * class has been overloaded or defined in the configuration file. 
     * Otherwise the default object classes will be loaded. The default objects
     * can also be defined using the configuration file.
     * 
     * @param string $taskName The name of the task
     * @return \PhpTaskDaemon\Task\Manager\AbstractClass 
	 */
	public function loadTask($taskName) {
		
	}


	/**
	 * 
	 * Action: Start Daemon
	 */
	public function start() {
		$tasks = $this->scanDirectoryForTasks(PROJECT_ROOT . '/app/Tasks/');
		
		// Initialize daemon
		foreach($tasks as $task) {
			try {
                $taskManager = \PhpTaskDaemon\Task\Factory::get($task);
			} catch (\Exception $e) {
				throw new \Exception('Failed loading task: ' . $task);
			}
		}
		// Start the Daemon
		$this->getDaemon()->start();
	}


	/**
	 * 
	 * Action: Stop daemon 
	 */
	public function stop() {
		if (!$this->getDaemon()->isRunning()) {
			echo 'Daemon is NOT running!!!' . "\n";
		} else {	
			echo 'Terminating application  !!!' . "\n";
			$this->getDaemon()->stop();
		}

		exit();
	}


	/**
	 * Alias for stopping and restarting the daemon.
	 */
	public function restart() {
		$this->stop();
		$this->start();
		exit;
	}


	/**
	 * 
	 * Action: Get daemon status
	 */
	public function status() {
		
		$status = State::getState();
		if ($status['pid'] === null) {
			echo "Daemon not running\n";
			exit;
		}
		echo var_dump($status);

		echo "PhpTaskDaemon - Status\n";
		echo "==========================\n";
		echo "\n";
		if (count($status['childs']) == 0) {
			echo "No processes!\n";
		} else {
			echo "Processes (" . count($status['childs']) . ")\n";

			foreach ($status['childs'] as $childPid) {
				$managerData = $status['task-' . $childPid];
				echo " - [" . $childPid . "]: " . $status['status-' . $childPid] . "\t(Queued: " . $managerData['statistics']['queued'] . "\tDone: " . $managerData['statistics']['done'] . "\tFailed:" . $managerData['statistics']['failed'] . ")\n";
				echo "  - [" . $childPid . "]: (" . $managerData['status']['percentage'] . ") => " . $managerData['status']['message'] . "\n";
			}

		}
		return true;
	}


	/**
	 * 
	 * Displays the current tasks and activities of the daemon. The monitor 
	 * action refreshes every x milliseconds.
	 */
	public function monitor() {
		$out  = "PhpTaskDaemon - Monitoring\n" .
				"==========================\n";
		echo "Function not yet implemented\n";
	}


	/**
	 * 
	 * Displays a help message containing usage instructions.
	 */
	public function help() {
		echo $this->_consoleOpts->getUsageMessage();
		exit;
	}

}
