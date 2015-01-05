<?php
namespace JenkinsApi\Jenkins;

use JenkinsApi\Jenkins;
use stdClass;


/**
 * Represents a node
 *
 * @package    JenkinsApi\Jenkins
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Node
{
    /**
     * @var stdClass
     */
    private $_node;

    /**
     * @var Jenkins
     */
    private $_jenkins;

    /**
     * @param stdClass $computer
     * @param Jenkins  $jenkins
     */
    public function __construct($computer, Jenkins $jenkins)
    {
        $this->_node = $computer;
        $this->_jenkins = $jenkins;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_node->displayName;
    }

    /**
     *
     * @return bool
     */
    public function isOffline()
    {
        return (bool)$this->_node->offline;
    }

    /**
     *
     * returns null when computer is launching
     * returns stdClass when computer has been put offline
     *
     * @return null|stdClass
     */
    public function getOfflineCause()
    {
        return $this->_node->offlineCause;
    }

    /**
     *
     * @return Node
     */
    public function toggleOffline()
    {
        $this->getJenkins()->toggleOfflineNode($this->getName());

        return $this;
    }

    /**
     *
     * @return Node
     */
    public function delete()
    {
        $this->getJenkins()->deleteNode($this->getName());

        return $this;
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->_jenkins;
    }

    /**
     * @return string
     */
    public function getConfiguration()
    {
        return $this->getJenkins()->getNodeConfiguration($this->getName());
    }
}
