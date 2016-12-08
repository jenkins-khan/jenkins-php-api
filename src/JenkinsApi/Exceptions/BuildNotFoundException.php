<?php

namespace JenkinsApi\Exceptions;

class BuildNotFoundException extends JenkinsApiException
{
    public function __construct($buildNumber, $jobName, $code = 0, \Exception $previous)
    {
        parent::__construct(sprintf("Build %u of Job '%s' not found", $buildNumber, $jobName), $code, $previous);
    }
}
