<?php

namespace JenkinsKhan\Jenkins;

use JenkinsKhan\Jenkins;

class TestReport
{

    /**
     * @var Jenkins
     */
    protected $jenkins;

    /**
     * @var \stdClass
     */
    protected $testReport;

    /**
     * @var string
     */
    protected $jobName;

    /**
     * @var int
     */
    protected $buildNumber;

    /**
     * __construct
     *
     * @param Jenkins   $jenkins
     * @param \stdClass $testReport
     * @param string    $jobName
     * @param int       $buildNumber
     */
    public function __construct(Jenkins $jenkins, \stdClass $testReport, $jobName, $buildNumber)
    {
        $this->jenkins     = $jenkins;
        $this->testReport  = $testReport;
        $this->jobName     = $jobName;
        $this->buildNumber = $buildNumber;
    }

    /**
     * @return string
     */
    public function getOriginalTestReport()
    {
        return json_encode($this->testReport);
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * @return int
     */
    public function getBuildNumber()
    {
        return $this->buildNumber;
    }

    /**
     * @return float
     */
    public function getDuration()
    {
        return $this->testReport->duration;
    }

    /**
     * @return int
     */
    public function getFailCount()
    {
        return $this->testReport->failCount;
    }

    /**
     * @return int
     */
    public function getPassCount()
    {
        return $this->testReport->passCount;
    }

    /**
     * @return int
     */
    public function getSkipCount()
    {
        return $this->testReport->skipCount;
    }

    /**
     * @return array
     */
    public function getSuites()
    {
        return $this->testReport->suites;
    }

    /**
     *
     * @return \stdClass
     */
    public function getSuite($id)
    {
        return $this->testReport->suites[$id];
    }

    /**
     *
     * @return string
     */
    public function getSuiteStatus($id)
    {
        $suite  = $this->getSuite($id);
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

