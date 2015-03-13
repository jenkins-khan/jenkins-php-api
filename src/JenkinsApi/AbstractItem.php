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

/**
 * Abstract class for all items that can be access via jenkins api
 *
 * @package    JenkinsApi
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
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
        $this->_data = $this->getJenkins()->get($this->getUrl());
        return $this;
    }

    /**
     * @return string
     */
    abstract protected function getUrl();

    /**
     * @param string $propertyName
     *
     * @return string|int|null|stdClass
     */
    public function get($propertyName)
    {
        if ($this->_data instanceof stdClass && property_exists($this->_data, $propertyName)) {
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

    public function __get($property)
    {
        return $this->get($property);
    }

    public function __isset($property)
    {
        return array_key_exists($property, $this->_data);
    }

    public function __call($name, $args = array())
    {
        if(strlen($name) > 3 && substr($name, 0, 3) === 'get') {
            return $this->get(lcfirst(substr($name, 3)));
        }
    }
}