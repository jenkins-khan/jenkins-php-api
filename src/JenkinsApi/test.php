<?php

require_once 'JenkinsNew.php';
require_once 'AbstractItem.php';
require_once 'Jenkins/Job.php';
require_once 'Jenkins/Build.php';

$j = new \JenkinsApi\Jenkins('http://ism-online-qa.jungheinrich.com:8080/jenkins');

$l = $j->getCurrentlyBuildingJobs();
var_dump($l);