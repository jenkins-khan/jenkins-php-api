<?php

namespace JenkinsApi\Exceptions;

class NodeNotFoundException extends JenkinsApiException
{
    public function __construct($nodeName, $code = 0, \Exception $previous)
    {
        parent::__construct(sprintf("Node '%s' not found", $nodeName), $code, $previous);
    }
}
