<?php

class Jenkins_View
{
  /**
   * @var stdClass
   */
  private $view;

  /**
   * @var Jenkins
   */
  protected $jenkins;


  /**
   * @param stdClass $view
   * @param Jenkins  $jenkins
   */
  public function __construct($view, Jenkins $jenkins)
  {
    $this->view = $view;
    $this->jenkins = $jenkins;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->view->name;
  }
  
  /**
   * @return Jenkins_Job[]
   */
  public function getJobs()
  {
    $jobs = array();
    
    foreach ($this->view->jobs as $job)
    {
      $jobs[] = $this->jenkins->getJob($job->name);
    }
    
    return $jobs;
  }

}
