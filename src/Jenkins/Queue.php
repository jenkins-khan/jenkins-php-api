<?php

namespace JenkinsKhan\Jenkins;

use JenkinsKhan\Jenkins;

class Queue
{

    /**
     * @var \stdClass
     */
    private $queue;

    /**
     * @var Jenkins
     */
    protected $jenkins;

    /**
     * @param \stdClass $queue
     * @param Jenkins   $jenkins
     */
    public function __construct($queue, Jenkins $jenkins)
    {
        $this->queue = $queue;
        $this->setJenkins($jenkins);
    }

    /**
     * @return array
     */
    public function getJobQueues()
    {
        $jobs = array();

        foreach ($this->queue->items as $item) {
            $jobs[] = new JobQueue($item, $this->getJenkins());
        }

        return $jobs;
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
     *
     * @return Queue
     */
    public function setJenkins(Jenkins $jenkins)
    {
        $this->jenkins = $jenkins;

        return $this;
    }
}
