<?php
namespace JenkinsApi\Item;

use JenkinsApi\AbstractItem;
use JenkinsApi\Jenkins;
use RuntimeException;
use stdClass;

/**
 * Represents a node
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 *
 * @method null|stdClass getOfflineCause() null when computer is launching, tdClass when computer has been put offline
 */
class Node extends AbstractItem
{
    /**
     * @var string
     */
    private $_nodeName;

    /**
     * @param string  $nodeName
     * @param Jenkins $jenkins
     */
    public function __construct($nodeName, Jenkins $jenkins)
    {
        $this->_nodeName = $nodeName;
        $this->_jenkins = $jenkins;

        $this->refresh();
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf('computer/%s/api/json', rawurlencode($this->_nodeName));
    }

    /**
     * @return Executor[]
     */
    public function getExecutors()
    {
        $executors = [];
        for ($i = 0; $i < $this->get('numExecutors'); $i++) {
            $executors[] = new Executor($i, $this->_nodeName, $this->getJenkins());
        }
        return $executors;
    }

    /**
     * @return Node
     */
    public function toggleOffline()
    {
        $response = $this->getJenkins()->post(sprintf('computer/%s/toggleOffline', $this->_nodeName));
        if($response) {
            throw new RuntimeException(sprintf('Error marking %s offline', $this->_nodeName));
        }
        return $this;
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->getJenkins()->post(sprintf('computer/%s/doDelete', $this->_nodeName));
    }

    /**
     * @return string
     */
    public function getConfiguration()
    {
        return $this->getJenkins()->get(
            sprintf('/computer/%s/config.xml', $this->_nodeName), [], [CURLOPT_RETURNTRANSFER => 1]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->get('displayName');
    }

    /**
     *
     * @return bool
     */
    public function isOffline()
    {
        return (bool)$this->get('offline');
    }
}
