<?php
namespace JenkinsApi\Item;

use JenkinsApi\AbstractItem;
use JenkinsApi\Jenkins;
use RuntimeException;

/**
 * Represents an executor
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 *
 * @method int getProgress()
 * @method int getNumber()
 */
class Executor extends AbstractItem
{
    /**
     * @var int
     */
    private $_executorId;
    /**
     * @var string
     */
    private $_nodeName;

    /**
     * @var Node
     */
    private $_node;

    /**
     * @param int     $executorId
     * @param string  $nodeName
     * @param Jenkins $jenkins
     */
    public function __construct($executorId, $nodeName, Jenkins $jenkins)
    {
        $this->_executorId = $executorId;
        $this->_nodeName = $nodeName;
        $this->_jenkins = $jenkins;

        $this->refresh();
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf('computer/%s/executors/%s/api/json', rawurlencode($this->_nodeName), rawurlencode($this->_executorId));
    }

    /**
     * @return Node
     */
    public function getNode()
    {
        if(!$this->_node) {
            $this->_node = new Node($this->_nodeName, $this->getJenkins());
        }
        return $this->_node;
    }

    /**
     * @return int|null
     */
    public function getBuildNumber()
    {
        if (($currentExecutable = $this->get('currentExecutable')) !== null) {
            return $currentExecutable->number;
        }
        return null;
    }

    /**
     * @return null|string
     */
    public function getBuildUrl()
    {
        if (($currentExecutable = $this->get('currentExecutable')) !== null) {
            return $currentExecutable->url;
        }
        return null;
    }

    /**
     * @return void
     */
    public function stop()
    {
        $response = $this->getJenkins()->post(
            sprintf('computer/%s/executors/%s/stop', $this->getNode()->getName(), $this->getNumber())
        );
        if($response) {
            throw new RuntimeException(sprintf('Error durring stopping executor #%s', $this->getNumber()));
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
