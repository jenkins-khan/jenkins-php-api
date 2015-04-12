<?php

namespace JenkinsKhan\Jenkins;

use JenkinsKhan\Jenkins;

class Computer
{

    /**
     * @var \stdClass
     */
    private $computer;

    /**
     * @var Jenkins
     */
    private $jenkins;


    /**
     * @param \stdClass $computer
     * @param Jenkins   $jenkins
     */
    public function __construct($computer, Jenkins $jenkins)
    {
        $this->computer = $computer;
        $this->setJenkins($jenkins);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->computer->displayName;
    }

    /**
     *
     * @return bool
     */
    public function isOffline()
    {
        return (bool) $this->computer->offline;
    }

    /**
     *
     * returns null when computer is launching
     * returns \stdClass when computer has been put offline
     *
     * @return null|\stdClass
     */
    public function getOfflineCause()
    {
        return $this->computer->offlineCause;
    }

    /**
     *
     * @return Computer
     */
    public function toggleOffline()
    {
        $this->getJenkins()->toggleOfflineComputer($this->getName());

        return $this;
    }

    /**
     *
     * @return Computer
     */
    public function delete()
    {
        $this->getJenkins()
             ->deleteComputer($this->getName());

        return $this;
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->jenkins;
    }

    /**
     * @param Jenkins $jenkins
     *
     * @return Computer
     */
    public function setJenkins(Jenkins $jenkins)
    {
        $this->jenkins = $jenkins;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfiguration()
    {
        return $this->getJenkins()->getComputerConfiguration($this->getName());
    }
}
