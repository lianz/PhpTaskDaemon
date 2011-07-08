<?php
/**
 * @package PhpTaskDaemon
 * @subpackage Task\Queue\Statistics
 * @copyright Copyright (C) 2011 Dirk Engels Websolutions. All rights reserved.
 * @author Dirk Engels <d.engels@dirkengels.com>
 * @license https://github.com/DirkEngels/PhpTaskDaemon/blob/master/doc/LICENSE
 */

namespace PhpTaskDaemon\Task\Queue\Statistics;

/**
 * 
 * The abstract queue statistics class implements methods for setting/change
 * the status count of executed jobs and the number of loaded and processed
 * items in the queue.
 */
abstract class AbstractClass {    

    const STATUS_LOADED = 'loaded';
    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_DONE = 'done';
    const STATUS_FAILED = 'failed';

    protected $_ipc;


    /**
     * 
     * The constructor sets the shared memory object. A default shared memory
     * object instance will be created when none provided.
     * @param \PhpTaskDaemon\Ipc $ipc
     */
    public function __construct(\PhpTaskDaemon\Daemon\Ipc\AbstractClass $ipc = null) {
        $this->setIpc($ipc);
    }


    /**
     * 
     * Unset the shared memory at destruction time.
     */
    public function __destruct() {
        if (is_a($this->_ipc, '\PhpTaskDaemon\Daemon\Ipc\Ipc')) {
            unset($this->_ipc);
        } 
    }


    /**
     *
     * Returns the shared memory object
     * @return PhpTaskDaemon\Ipc
     */
    public function getIpc() {
        return $this->_ipc;
    }


    /**
     *
     * Sets a shared memory object
     * @param \PhpTaskDaemon\Daemon\Ipc\Ipc $ipc
     * @return $this
     */
    public function setIpc($ipc) {
        if (!is_a($ipc, '\PhpTaskDaemon\Daemon\Ipc\AbstractClass')) {
            $ipc = new \PhpTaskDaemon\Daemon\Ipc\None(
                'statistics-' . getmypid()
            );
        }
        $this->_ipc = $ipc;
        $this->_initializeStatus(self::STATUS_LOADED);
        $this->_initializeStatus(self::STATUS_QUEUED);
        $this->_initializeStatus(self::STATUS_RUNNING);
        $this->_initializeStatus(self::STATUS_DONE);
        $this->_initializeStatus(self::STATUS_FAILED);
        return true;
    }


    /**
     * Returns an array with the number of executed tasks grouped per status.
     * 
     * @param string $status
     * @return array
     */
    public function get($status = null) {
        if (is_null($status)) {
            return $this->_ipc->get();
        }
        if (!in_array($status, $this->_ipc->getKeys())) {
            $this->_initializeStatus($status);
        }
        return $this->_ipc->getVar($status);
    }


    /**
     * 
     * (Re)Sets a status count
     * @param string $status
     * @param integer $count
     * @return bool
     */
    public function setStatusCount($status = self::STATUS_DONE, $count = 0) {
        if (!in_array($status, $this->_ipc->getKeys())) {
            $this->_initializeStatus($status);
        }
        return $this->_ipc->setVar($status, $count);
    }


    /**
     * Increments the statistics for a certain status
     * 
     * @param string $status
     * @return integer
     */
    public function incrementStatus($status = self::STATUS_DONE) {
        return $this->_ipc->incrementVar($status);
    }


    /**
     * 
     * (Re)Sets the queue count.
     * @param integer $count
     */
    public function setQueueCount($count = 0) {
        $this->setStatusCount(self::STATUS_QUEUED, $count);
        $this->setStatusCount(self::STATUS_LOADED, $count);
        return $count;
    }


    /**
     * 
     * Decrements the queue count (after finishing a single job).
     */
    public function decrementQueue() {
        return $this->_ipc->decrementVar(self::STATUS_QUEUED);
    }


    /**
     * Initializes the statistics array for a certain status.
     * 
     * @param string $status
     * @return bool
     */
    private function _initializeStatus($status) {
        $keys = $this->_ipc->getKeys();
        if (!in_array($status, array_keys($keys))) {
            $this->_ipc->setVar($status, 0);
            return true;
        }
        return false;
    }

}