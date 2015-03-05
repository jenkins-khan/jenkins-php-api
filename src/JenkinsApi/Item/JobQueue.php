<?php
namespace JenkinsApi\Item;

use JenkinsApi\Jenkins;
use RuntimeException;
use stdClass;

/**
 * Control the jenkins job queue
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class JobQueue
{
    /**
     * @var stdClass
     */
    private $_jobQueue;


    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @param stdClass $jobQueue
     * @param Jenkins  $jenkins
     */
    public function __construct($jobQueue, Jenkins $jenkins)
    {
        $this->_jobQueue = $jobQueue;
        $this->_jenkins = $jenkins;
    }

    /**
     * @return array
     */
    public function getInputParameters()
    {
        $parameters = array();

        if (!property_exists($this->_jobQueue->actions[0], 'parameters')) {
            return $parameters;
        }

        foreach ($this->_jobQueue->actions[0]->parameters as $parameter) {
            $parameters[$parameter->name] = $parameter->value;
        }

        return $parameters;
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->_jobQueue->task->name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_jobQueue->id;
    }

    /**
     * @return void
     */
    public function cancel()
    {
        $response = $this->getJenkins()->post(sprintf('queue/item/%s/cancelQueue', $this->getId()));
        if ($response) {
            throw new RuntimeException(sprintf('Error durring stopping job queue #%s', $this->getId()));
        }
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->_jenkins;
    }
}
