<?php
/**
 * @package PhpTaskDaemon
 * @subpackage Task\Manager\Process
 * @copyright Copyright (C) 2011 Dirk Engels Websolutions. All rights reserved.
 * @author Dirk Engels <d.engels@dirkengels.com>
 * @license https://github.com/DirkEngels/PhpTaskDaemon/blob/master/doc/LICENSE
 */

namespace PhpTaskDaemon\Task\Manager\Process;

class Same extends AbstractClass implements InterfaceClass {

    public function run() {
        $this->getQueue()->getStatistics()->getIpc()->setVar('executors', array(getmypid()));

        foreach($this->getJobs() as $job) {
            $this->_processTask($job);
        }

        \PhpTaskDaemon\Daemon\Logger::get()->log('Finished current set of tasks!', \Zend_Log::INFO);
    }

}
