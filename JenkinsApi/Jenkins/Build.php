<?php
namespace JenkinsApi\Jenkins;

use JenkinsApi\Jenkins;
use stdClass;

/**
 * Represents a single build
 *
 * @package    JenkinsApi\Jenkins
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Build
{
    /**
     * @var string
     */
    const FAILURE = 'FAILURE';

    /**
     * @var string
     */
    const SUCCESS = 'SUCCESS';

    /**
     * @var string
     */
    const RUNNING = 'RUNNING';

    /**
     * @var string
     */
    const WAITING = 'WAITING';

    /**
     * @var string
     */
    const UNSTABLE = 'UNSTABLE';

    /**
     * @var string
     */
    const ABORTED = 'ABORTED';

    /**
     * @var stdClass
     */
    private $_build;

    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @param stdClass $build
     * @param Jenkins  $jenkins
     */
    public function __construct($build, Jenkins $jenkins)
    {
        $this->_build = $build;
        $this->_jenkins = $jenkins;
    }

    /**
     * @return array
     */
    public function getInputParameters()
    {
        $parameters = array();

        if (!property_exists($this->_build->actions[0], 'parameters')) {
            return $parameters;
        }

        foreach ($this->_build->actions[0]->parameters as $parameter) {
            $parameters[$parameter->name] = $parameter->value;
        }

        return $parameters;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        //division par 1000 => pas de millisecondes
        return $this->_build->timestamp / 1000;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        //division par 1000 => pas de millisecondes
        return $this->_build->duration / 1000;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->_build->number;
    }

    /**
     * @return null|int
     */
    public function getProgress()
    {
        $progress = null;
        if (null !== ($executor = $this->getExecutor())) {
            $progress = $executor->getProgress();
        }

        return $progress;
    }

    /**
     * @return float|null
     */
    public function getEstimatedDuration()
    {
        //since version 1.461 estimatedDuration is displayed in jenkins's api
        //we can use it witch is more accurate than calcule ourselves
        //but older versions need to continue to work, so in case of estimated
        //duration is not found we fallback to calcule it.
        if (property_exists($this->_build, 'estimatedDuration')) {
            return $this->_build->estimatedDuration / 1000;
        }

        $duration = null;
        $progress = $this->getProgress();
        if (null !== $progress && $progress >= 0) {
            $duration = ceil((time() - $this->getTimestamp()) / ($progress / 100));
        }

        return $duration;
    }


    /**
     * Returns remaining execution time (seconds)
     *
     * @return int|null
     */
    public function getRemainingExecutionTime()
    {
        $remaining = null;
        if (null !== ($estimatedDuration = $this->getEstimatedDuration())) {
            //be carefull because time from JK server could be different
            //of time from Jenkins server
            //but i didn't find a timestamp given by Jenkins api

            $remaining = $estimatedDuration - (time() - $this->getTimestamp());
        }

        return max(0, $remaining);
    }

    /**
     * @return null|string
     */
    public function getResult()
    {
        $result = null;
        switch ($this->_build->result) {
            case 'FAILURE':
                $result = Build::FAILURE;
                break;
            case 'SUCCESS':
                $result = Build::SUCCESS;
                break;
            case 'UNSTABLE':
                $result = Build::UNSTABLE;
                break;
            case 'ABORTED':
                $result = Build::ABORTED;
                break;
            case 'WAITING':
                $result = Build::WAITING;
                break;
            default:
                $result = Build::RUNNING;
                break;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_build->url;
    }

    /**
     * @return Executor|null
     */
    public function getExecutor()
    {
        if (!$this->isRunning()) {
            return null;
        }

        $runExecutor = null;
        foreach ($this->getJenkins()->getExecutors() as $executor) {
            /** @var Executor $executor */

            if ($this->getUrl() === $executor->getBuildUrl()) {
                $runExecutor = $executor;
            }
        }

        return $runExecutor;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return Build::RUNNING === $this->getResult();
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
    public function getBuiltOn()
    {
        return $this->_build->builtOn;
    }

    /**
     * @return bool
     */
    public function isBuilding()
    {
        return $this->_build->building;
    }
}
