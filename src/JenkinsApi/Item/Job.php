<?php
namespace JenkinsApi\Item;

use JenkinsApi\AbstractItem;
use JenkinsApi\Jenkins;
use RuntimeException;

/**
 *
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Job extends AbstractItem
{
    /**
     * @var int
     */
    protected $_timeoutSeconds = 86400;

    protected $_checkIntervalSeconds = 5;

    /**
     * @var
     */
    private $_jobName;

    /**
     * @param         $jobName
     * @param Jenkins $jenkins
     */
    public function __construct($jobName, Jenkins $jenkins)
    {
        $this->jobName = $jobName;
        $this->_jenkins = $jenkins;

        $this->refresh();
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf('job/%s/api/json', $this->_jobName);
    }

    /**
     * @return Build[]
     */
    public function getBuilds()
    {
        $builds = array();
        foreach ($this->_data->builds as $build) {
            $builds[] = $this->getBuild($build->number);
        }

        return $builds;
    }

    /**
     * @param int $buildId
     *
     * @return Build
     * @throws RuntimeException
     */
    public function getBuild($buildId)
    {
        return $this->_jenkins->getBuild($this->getName(), $buildId);
    }

    /**
     * @return array
     */
    public function getParametersDefinition()
    {
        $parameters = array();

        foreach ($this->_data->actions as $action) {
            if (!property_exists($action, 'parameterDefinitions')) {
                continue;
            }

            foreach ($action->parameterDefinitions as $parameterDefinition) {
                $default = property_exists($parameterDefinition, 'defaultParameterValue')
                    ? $parameterDefinition->defaultParameterValue->value
                    : null;
                $description = property_exists($parameterDefinition, 'description')
                    ? $parameterDefinition->description
                    : null;
                $choices = property_exists($parameterDefinition, 'choices')
                    ? $parameterDefinition->choices
                    : null;

                $parameters[$parameterDefinition->name] = array(
                    'default'     => $default,
                    'choices'     => $choices,
                    'description' => $description,
                );
            }
        }

        return $parameters;
    }

    /**
     * @return Build|null
     */
    public function getLastSuccessfulBuild()
    {
        if (null === $this->_data->lastSuccessfulBuild) {
            return null;
        }

        return $this->_jenkins->getBuild($this->getName(), $this->_data->lastSuccessfulBuild->number);
    }

    /**
     * @return Build|null
     */
    public function getLastBuild()
    {
        if (null === $this->_data->lastBuild) {
            return null;
        }
        return $this->_jenkins->getBuild($this->getName(), $this->_data->lastBuild->number);
    }

    /**
     * @return bool
     */
    public function isCurrentlyBuilding()
    {
        return $this->getLastBuild()->isBuilding();
    }

    /**
     * @param array $parameters
     *
     * @return bool
     */
    public function launch($parameters = array())
    {
        if (empty($parameters)) {
            $this->_jenkins->post(sprintf('job/%s/build', $this->_jobName));
        } else {
            $this->_jenkins->post(sprintf('job/%s/buildWithParameters', $this->_jobName), $parameters);
        }

        return true;
    }

    /**
     * @param array $parameters
     *
     * @return Build|bool
     */
    public function launchAndWait($parameters = array())
    {
        if(!$this->isCurrentlyBuilding()) {
            $lastNumber = $this->getLastBuild()->getNumber();
            $startTime = time();
            $this->launch($parameters);

            $build = $this->getLastBuild();

            while(
                (time() < $startTime + $this->_timeoutSeconds) &&
                ($build->getNumber() == $lastNumber + 1 && !$build->isBuilding())
            ) {
                sleep($this->_checkIntervalSeconds);
                $build->refresh();
            }

            return $build;

        }
        return false;
    }

    /**
     * @return boolean
     */
    public function isBuildable()
    {
        return $this->_data->buildable;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->get('color');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }
}
