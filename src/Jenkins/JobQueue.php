<?php

namespace JenkinsKhan\Jenkins;

use JenkinsKhan\Jenkins;

class JobQueue
{

    /**
     * @var \stdClass
     */
    private $jobQueue;


    /**
     * @var Jenkins
     */
    protected $jenkins;

    /**
     * @param \stdClass $jobQueue
     * @param Jenkins   $jenkins
     */
    public function __construct($jobQueue, Jenkins $jenkins)
    {
        $this->jobQueue = $jobQueue;
        $this->setJenkins($jenkins);
    }

    /**
     * @return array
     */
    public function getInputParameters()
    {
        $parameters = array();

        if (!property_exists($this->jobQueue->actions[0], 'parameters')) {
            return $parameters;
        }

        foreach ($this->jobQueue->actions[0]->parameters as $parameter) {
            $parameters[$parameter->name] = $parameter->value;
        }

        return $parameters;
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->jobQueue->task->name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->jobQueue->id;
    }

    /**
     * @return void
     */
    public function cancel()
    {
        $this->getJenkins()->cancelQueue($this);
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->jenkins;
    }

    /**
     * @param Jenkins $jenkins
     */
    public function setJenkins(Jenkins $jenkins)
    {
        $this->jenkins = $jenkins;

        return $this;
    }
}
