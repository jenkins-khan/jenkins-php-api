<?php

namespace JenkinsKhan\Jenkins;

use JenkinsKhan\Jenkins;

class View
{

    /**
     * @var \stdClass
     */
    private $view;

    /**
     * @var Jenkins
     */
    protected $jenkins;


    /**
     * @param \stdClass $view
     * @param Jenkins   $jenkins
     */
    public function __construct($view, Jenkins $jenkins)
    {
        $this->view    = $view;
        $this->jenkins = $jenkins;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->view->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return (isset($this->view->description)) ? $this->view->description : null;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return (isset($this->view->url)) ? $this->view->url : null;
    }

    /**
     * @return Job[]
     */
    public function getJobs()
    {
        $jobs = array();

        foreach ($this->view->jobs as $job) {
            $jobs[] = $this->jenkins->getJob($job->name);
        }

        return $jobs;
    }

    /**
     * getColor
     *
     * @return string
     */
    public function getColor()
    {
        $color = 'blue';
        foreach ($this->view->jobs as $job) {
            if ($this->getColorPriority($job->color) > $this->getColorPriority($color)) {
                $color = $job->color;
            }
        }

        return $color;
    }

    /**
     * getColorPriority
     *
     * @param string $color
     *
     * @return int
     */
    protected function getColorPriority($color)
    {
        switch ($color) {
            default:
                return 999;
            case 'red_anime':
                return 11;
            case 'red':
                return 10;
            case 'yellow_anime':
                return 6;
            case 'yellow':
                return 5;
            case 'blue_anime':
                return 2;
            case 'blue':
                return 1;
            case 'disabled':
                return 0;
        }
    }
}
