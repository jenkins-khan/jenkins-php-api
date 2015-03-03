<?php
namespace JenkinsApi\Item;

use JenkinsApi\Jenkins;
use stdClass;

/**
 * Control a Jenkins view
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class View
{
    /**
     * @var stdClass
     */
    private $_view;

    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @param stdClass $view
     * @param Jenkins  $jenkins
     */
    public function __construct($view, Jenkins $jenkins)
    {
        $this->_view = $view;
        $this->_jenkins = $jenkins;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_view->name;
    }

    /**
     * @return Job[]
     */
    public function getJobs()
    {
        $jobs = array();

        foreach ($this->_view->jobs as $job) {
            $jobs[] = $this->_jenkins->getJob($job->name);
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
        foreach ($this->_view->jobs as $job) {
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
