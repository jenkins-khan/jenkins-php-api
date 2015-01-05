<?php
namespace JenkinsApi\Jenkins;

use JenkinsApi\Jenkins;
use stdClass;


/**
 * Represents an executor
 *
 * @package    JenkinsApi\Jenkins
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Executor
{
    /**
     * @var stdClass
     */
    private $_executor;

    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @var string
     */
    protected $_node;

    /**
     * @param stdClass $executor
     * @param string   $computer
     * @param Jenkins  $jenkins
     */
    public function __construct($executor, $computer, Jenkins $jenkins)
    {
        $this->_executor = $executor;
        $this->_node = $computer;
        $this->_jenkins = $jenkins;
    }

    /**
     * @return string
     */
    public function getNode()
    {
        return $this->_node;
    }

    /**
     * @return int
     */
    public function getProgress()
    {
        return $this->_executor->progress;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->_executor->number;
    }


    /**
     * @return int|null
     */
    public function getBuildNumber()
    {
        $number = null;
        if (isset($this->_executor->currentExecutable)) {
            $number = $this->_executor->currentExecutable->number;
        }

        return $number;
    }

    /**
     * @return null|string
     */
    public function getBuildUrl()
    {
        $url = null;
        if (isset($this->_executor->currentExecutable)) {
            $url = $this->_executor->currentExecutable->url;
        }

        return $url;
    }

    /**
     * @return void
     */
    public function stop()
    {
        $this->getJenkins()->stopExecutor($this);
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->_jenkins;
    }
}
