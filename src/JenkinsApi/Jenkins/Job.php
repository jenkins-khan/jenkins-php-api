<?php
namespace JenkinsApi\Jenkins;

use JenkinsApi\AbstractItem;
use JenkinsApi\Jenkins;
use RuntimeException;

/**
 *
 *
 * @package    JenkinsApi\Jenkins
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Job extends AbstractItem
{
    /**
     * @var
     */
    private $_jobName;

    /**
     * @param         $jobName
     * @param Jenkins $jenkins
     *
     * @internal param stdClass $jobData
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
        return sprintf('%s/job/%s/api/json', $this->_jenkins->getBaseUrl(), $this->_jobName);
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
     * @return string
     */
    public function getColor()
    {
        return $this->_data->color;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_data->name;
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
     * @return boolean
     */
    public function isBuildable()
    {
        return $this->_data->buildable;
    }

    /**
     * @return bool
     */
    public function isCurrentlyBuilding()
    {
        return $this->getLastBuild()->isBuilding();
    }
}
