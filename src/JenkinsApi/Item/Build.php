<?php
namespace JenkinsApi\Item;

use JenkinsApi\AbstractItem;
use JenkinsApi\Jenkins;

/**
 * Represents a single build
 *
 * @package    JenkinsApi\Item
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 *
 * @method int getNumber()
 * @method string getBuiltOn()
 */
class Build extends AbstractItem
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
     * @var string
     */
    protected $_buildNumber;

    /**
     * @var string
     */
    protected $_jobName;

    /**
     * @param string  $buildNumber
     * @param string  $jobName
     * @param Jenkins $jenkins
     */
    public function __construct($buildNumber, $jobName, Jenkins $jenkins)
    {
        $this->_buildNumber = $buildNumber;
        $this->_jobName = (string) $jobName;
        $this->_jenkins = $jenkins;

        $this->refresh();
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf('job/%s/%d/api/json', rawurlencode($this->_jobName), rawurlencode($this->_buildNumber));
    }

    /**
     * @return array
     */
    public function getInputParameters()
    {
        $parameters = array();
        if (($this->get('actions')) === null || $this->get('actions') === array()) {
            return $parameters;
        }

        foreach ($this->get('actions') as $action) {
            if (property_exists($action, 'parameters')) {
                foreach ($action->parameters as $parameter) {
                    if (property_exists($parameter, 'value')) {
                        $parameters[$parameter->name] = $parameter->value;
                    } elseif (property_exists($parameter, 'number') && property_exists($parameter, 'jobName')) {
                        $parameters[$parameter->name]['number'] = $parameter->number;
                        $parameters[$parameter->name]['jobName'] = $parameter->jobName;
                    }
                }
                break;
            }
        }

        return $parameters;
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
        if ($this->get('estimatedDuration')) {
            return $this->get('estimatedDuration') / 1000;
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
        switch ($this->get('result')) {
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
     * @return Executor|null
     */
    public function getExecutor()
    {
        if (!$this->isBuilding()) {
            return null;
        }

        $runExecutor = null;
        foreach ($this->getJenkins()->getExecutors() as $executor) {
            /** @var Executor $executor */
            if ($this->getBuildUrl() === $executor->getBuildUrl()) {
                $runExecutor = $executor;
            }
        }
        return $runExecutor;
    }

    /**
     * @param $text
     *
     * @return bool
     */
    public function setDescription($text)
    {
        $url = sprintf('job/%s/%s/submitDescription', $this->_jobName, $this->_buildNumber);
        return $this->getJenkins()->post($url, array('description' => $text));
    }

    /**
     * @return string
     */
    public function getConsoleTextBuild()
    {
        return $this->getJenkins()->get(sprintf('job/%s/%s/consoleText', $this->_jobName, $this->_buildNumber), 1, array(), array(), true);
    }

    /**
     * @return bool
     */
    public function isBuilding()
    {
        return (bool)$this->get('building');
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return (int) ($this->get('timestamp') / 1000);
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        if ($this->get('duration') == 0) {
            // duration is not set by Jenkins, let's calculate ourselves
            return (int) (time() - $this->getTimestamp());
        }
        return (int) ($this->get('duration') / 1000);
    }

    /**
     * @return string
     */
    public function getBuildUrl()
    {
        return $this->get('url');
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->_jobName;
    }

    /**
     * @return bool
     */
    public function stop()
    {
        if (!$this->isBuilding()) {
            return null;
        }

        return $this->getJenkins()->post(
            sprintf('job/%s/%d/stop', $this->_jobName, $this->_buildNumber)
        );
    }
}
