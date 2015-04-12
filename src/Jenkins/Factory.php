<?php

namespace JenkinsKhan\Jenkins;

use JenkinsKhan\Jenkins;

class Factory
{

    /**
     * @param string $url
     *
     * @return Jenkins
     */
    public function build($url)
    {
        return new Jenkins($url);
    }
}
