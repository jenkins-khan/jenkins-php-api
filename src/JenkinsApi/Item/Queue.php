<?php
namespace JenkinsApi\Item;

use JenkinsApi\AbstractItem;
use JenkinsApi\Jenkins;
use stdClass;

/**
 * Control the Jenkins queue
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Queue extends AbstractItem
{
    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @param Jenkins  $jenkins
     */
    public function __construct(Jenkins $jenkins)
    {
        $this->_jenkins = $jenkins;

        $this->refresh();
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return 'queue/api/json';
    }

    /**
     * @return JobQueue[]
     */
    public function getJobQueues()
    {
        $jobs = array();
        foreach ($this->get('items') as $item) {
            $jobs[] = new JobQueue($item, $this->getJenkins());
        }

        return $jobs;
    }
}
