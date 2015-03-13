<?php
namespace JenkinsApi\Item;

use JenkinsApi\AbstractItem;
use JenkinsApi\Jenkins;
use stdClass;

/**
 * Control a Jenkins view
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class View extends AbstractItem
{
    /**
     * @var string
     */
    private $_name;

    /**
     * @param string $name
     * @param Jenkins  $jenkins
     */
    public function __construct($name, Jenkins $jenkins)
    {
        $this->_name = $name;
        $this->_jenkins = $jenkins;

        $this->refresh();
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf('view/%s/api/json', rawurlencode($this->_name));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return Job[]
     */
    public function getJobs()
    {
        $jobs = array();
        foreach ($this->get('jobs') as $job) {
            $jobs[] = new Job($job->name, $this);
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
        foreach ($this->get('jobs') as $job) {
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
