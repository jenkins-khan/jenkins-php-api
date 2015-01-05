<?php
namespace JenkinsApi\Jenkins;

use DOMDocument;
use JenkinsApi\Jenkins;
use RuntimeException;
use stdClass;

/**
 *
 *
 * @package    JenkinsApi\Jenkins
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Job
{
    /**
     * @var stdClass
     */
    private $_job;

    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @param stdClass $job
     * @param Jenkins  $jenkins
     */
    public function __construct($job, Jenkins $jenkins)
    {
        $this->_job = $job;
        $this->_jenkins = $jenkins;
    }

    /**
     * @return Build[]
     */
    public function getBuilds()
    {
        $builds = array();
        foreach ($this->_job->builds as $build) {
            $builds[] = $this->getJenkinsBuild($build->number);
        }

        return $builds;
    }

    /**
     * @param int $buildId
     *
     * @return Build
     * @throws RuntimeException
     */
    public function getJenkinsBuild($buildId)
    {
        return $this->getJenkins()->getBuild($this->getName(), $buildId);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_job->name;
    }

    /**
     * @return array
     */
    public function getParametersDefinition()
    {
        $parameters = array();

        foreach ($this->_job->actions as $action) {
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
        return $this->_job->color;
    }

    /**
     * @return string
     *
     * @throws RuntimeException
     */
    public function retrieveXmlConfigAsString()
    {
        return $this->_jenkins->retrieveXmlConfigAsString($this->getName());
    }

    /**
     * @return DOMDocument
     */
    public function retrieveXmlConfigAsDomDocument()
    {
        $document = new DOMDocument;
        $document->loadXML($this->retrieveXmlConfigAsString());
        return $document;
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->_jenkins;
    }

    /**
     * @return Build|null
     */
    public function getLastSuccessfulBuild()
    {
        if (null === $this->_job->lastSuccessfulBuild) {
            return null;
        }

        return $this->getJenkins()->getBuild($this->getName(), $this->_job->lastSuccessfulBuild->number);
    }
}
