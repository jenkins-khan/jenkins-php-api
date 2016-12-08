<?php

namespace JenkinsApi\Exceptions;

class JobNotFoundException extends JenkinsApiException
{
    public function __construct($jobname, $code = 0, \Exception $previous)
    {
        parent::__construct(sprintf("Job '%s' not found", $jobname), $code, $previous);
    }
}
