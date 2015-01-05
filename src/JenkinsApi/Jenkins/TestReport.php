<?php
namespace JenkinsApi\Jenkins;

use JenkinsApi\Jenkins;
use stdClass;

/**
 *
 *
 * @package    JenkinsApi\Jenkins
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class TestReport
{
    /**
     * @var Jenkins
     */
    protected $_jenkins;

    /**
     * @var stdClass
     */
    protected $_testReport;

    /**
     * @var string
     */
    protected $_jobName;

    /**
     * @var int
     */
    protected $_buildNumber;

    /**
     * __construct
     *
     * @param Jenkins  $jenkins
     * @param stdClass $testReport
     * @param string   $jobName
     * @param int      $buildNumber
     */
    public function __construct(Jenkins $jenkins, stdClass $testReport, $jobName, $buildNumber)
    {
        $this->_jenkins = $jenkins;
        $this->_testReport = $testReport;
        $this->_jobName = $jobName;
        $this->_buildNumber = $buildNumber;
    }

    /**
     * @return string
     */
    public function getOriginalTestReport()
    {
        return json_encode($this->_testReport);
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->_jobName;
    }

    /**
     * @return int
     */
    public function getBuildNumber()
    {
        return $this->_buildNumber;
    }

    /**
     * @return float
     */
    public function getDuration()
    {
        return $this->_testReport->duration;
    }

    /**
     * @return int
     */
    public function getFailCount()
    {
        return $this->_testReport->failCount;
    }

    /**
     * @return int
     */
    public function getPassCount()
    {
        return $this->_testReport->passCount;
    }

    /**
     * @return int
     */
    public function getSkipCount()
    {
        return $this->_testReport->skipCount;
    }

    /**
     * @return array
     */
    public function getSuites()
    {
        return $this->_testReport->suites;
    }

    /**
     *
     * @return stdClass
     */
    public function getSuite($id)
    {
        return $this->_testReport->suites[$id];
    }

    /**
     *
     * @return string
     */
    public function getSuiteStatus($id)
    {
        $suite = $this->getSuite($id);
        $status = 'PASSED';
        foreach ($suite->cases as $case) {
            if ($case->status == 'FAILED') {
                $status = 'FAILED';
                break;
            }
        }
        return $status;
    }
}

