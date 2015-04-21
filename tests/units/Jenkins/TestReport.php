<?php
namespace tests\units\JenkinsKhan\Jenkins;

require_once __DIR__ . '/../../bootstrap.php';

use mageekguy\atoum;

class TestReport extends atoum\test
{

  public function setUp()
  {
    $this->mockGenerator->generate('\Jenkins');
    return true;
  }

  public function test__construct()
  {
    $jenkins    = new \mock\JenkinsKhan\Jenkins('url');

    $reportJson = json_decode(file_get_contents(__DIR__ . '/report_passed.json'));
    $report     = new \JenkinsKhan\Jenkins\TestReport($jenkins, $reportJson, $jobName = 'units', $buildNumber = '32');
    $this->assert->variable($report->getJobName())->isEqualTo($jobName);
    $this->assert->variable($report->getBuildNumber())->isEqualTo($buildNumber);
    $this->assert->float($report->getDuration())->isEqualTo(0.3716328);
    $this->assert->integer($report->getFailCount())->isEqualTo(0);
    $this->assert->integer($report->getSkipCount())->isEqualTo(0);
    $this->assert->integer($report->getPassCount())->isEqualTo(9);
    $this->assert->phpArray($report->getSuites())->hasSize(4);
  }

  public function test_getOriginalTestReport()
  {
    $jenkins    = new \mock\JenkinsKhan\Jenkins('url');

    $reportJson = json_decode(file_get_contents(__DIR__ . '/report_passed.json'));
    $report     = new \JenkinsKhan\Jenkins\TestReport($jenkins, $reportJson, $jobName = 'units', $buildNumber = '32');

    $this->assert->string($report = $report->getOriginalTestReport())
      ->isNotEmpty()
      ->hasLength(2528)
      ->contains('"failCount":0')
      ->contains('"passCount":9')
    ;

    $this->assert->array(json_decode($report, true))
      ->isNotEmpty()
      ->hasSize(5)
    ;
  }

  public function test_getSuite()
  {
    $jenkins    = new \mock\JenkinsKhan\Jenkins('url');

    $reportJson = json_decode(file_get_contents(__DIR__ . '/report_passed.json'));
    $report     = new \JenkinsKhan\Jenkins\TestReport($jenkins, $reportJson, $jobName = 'units', $buildNumber = '32');

    $this->assert->object($suite = $report->getSuite(0))->isInstanceOf('stdClass');
    $this->assert->phpArray($suite->cases)->hasSize(1);
    $this->assert->float($suite->duration)->isEqualTo(0.06950712);
  }

  /**
   * @dataProvider getSuiteStatusDataProvider
   */
  public function testGetSuiteStatusDataProvider($file, $suiteId, $status)
  {
    $jenkins    = new \mock\JenkinsKhan\Jenkins('url');

    $reportJson = json_decode(file_get_contents(__DIR__ . '/' . $file));
    $report     = new \JenkinsKhan\Jenkins\TestReport($jenkins, $reportJson, $jobName = 'units', $buildNumber = '32');

    $this->assert->string($suite = $report->getSuiteStatus($suiteId))->isEqualTo($status);
  }

  /**
   * @return array
   */
  public function getSuiteStatusDataProvider()
  {
    //file - suiteid - status
    return array(
      array('report_passed.json', 0, 'PASSED'),
      array('report_passed.json', 1, 'PASSED'),
      array('report_passed.json', 2, 'PASSED'),
      array('report_passed.json', 3, 'PASSED'),

      array('report_failed.json', 0, 'PASSED'),
      array('report_failed.json', 1, 'FAILED'),
      array('report_failed.json', 2, 'PASSED'),
      array('report_failed.json', 3, 'PASSED'),
    );
  }

}
