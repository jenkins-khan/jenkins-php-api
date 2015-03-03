<?php
namespace JenkinsApi\Item;

use JenkinsApi\Jenkins;
use stdClass;

/**
 * Control the Jenkins queue
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Queue
{
    /**
     * @var stdClass
     */
    private $_queue;

    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @param stdClass $queue
     * @param Jenkins  $jenkins
     */
    public function __construct($queue, Jenkins $jenkins)
    {
        $this->_queue = $queue;
        $this->_jenkins = $jenkins;
    }

    /**
     * @return array
     */
    public function getJobQueues()
    {
        $jobs = array();

        foreach ($this->_queue->items as $item) {
            $jobs[] = new JobQueue($item, $this->getJenkins());
        }

        return $jobs;
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->_jenkins;
    }
}
