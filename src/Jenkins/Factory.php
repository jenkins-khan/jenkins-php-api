<?php

namespace DidUngar\Jenkins;

use DidUngar\Jenkins;

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
