<?php
/**
 * AbstractItem.php
 *
 * @package    Atlas
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @copyright  2013 Jungheinrich AG
 * @license    Proprietary license
 * @version    $Id$
 */

namespace JenkinsApi;

use stdClass;

abstract class AbstractItem
{
    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @var stdClass
     */
    protected $_data;

    /**
     * @return $this
     */
    public function refresh()
    {
        $this->_data = $this->$this->_jenkins->get($this->getUrl());

        return $this;
    }

    /**
     * @return string
     */
    abstract protected function getUrl();

    /**
     * @param string $propertyName
     *
     * @return string|int|null
     */
    public function get($propertyName)
    {
        if ($this->_data instanceof stdClass && isset($this->_data->$propertyName)) {
            return $this->_data->$propertyName;
        }

        return null;
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->_jenkins;
    }

    /**
     * @param Jenkins $jenkins
     */
    public function setJenkins($jenkins)
    {
        $this->_jenkins = $jenkins;
    }
}