<?php
namespace JenkinsApi\Item;

use JenkinsApi\AbstractItem;
use JenkinsApi\Jenkins;

/**
 * Represents a single build, created from the LastBuild
 *
 * @package    JenkinsApi\Item
 * @author     Yorick Terweijden <yt@productsup.com>
 * @version    $Id$
 *
 * @method int getNumber()
 * @method string getBuiltOn()
 */
class LastBuild extends Build
{
    /**
     * @param string  $jobName
     * @param Jenkins $jenkins
     */
    public function __construct($jobName, Jenkins $jenkins)
    {
        $this->_jobName = (string) $jobName;
        $this->_jenkins = $jenkins;

        $this->refresh();

        $this->_buildNumber = $this->get('number');
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf('job/%s/lastBuild/api/json', rawurlencode($this->_jobName));
    }
}
