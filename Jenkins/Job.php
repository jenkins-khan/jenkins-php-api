<?php

class Jenkins_Job
{
  
  /**
   * @var stdClass
   */
  private $job;

  /**
   * @var Jenkins
   */
  protected $jenkins;

  /**
   * @param stdClass $job
   * @param \Jenkins $jenkins
   */
  public function __construct($job, Jenkins $jenkins)
  {
    $this->job = $job;

    $this->setJenkins($jenkins);
  }

  /**
   * @return Jenkins_Build[]
   */
  public function getBuilds()
  {
    $builds = array();
    foreach ($this->job->builds as $build)
    {
      $builds[] = $this->getJenkinsBuild($build->number);
    }
    
    return $builds;
  }


  /**
   * @param int $buildId
   *
   * @return Jenkins_Build
   * @throws RuntimeException
   */
  public function getJenkinsBuild($buildId)
  {
    return $this->getJenkins()->getBuild($this->getName(), $buildId);
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->job->name;
  }

  /**
   * @return array
   */
  public function getParametersDefinition()
  {
    $parameters = array();
    
    foreach ($this->job->actions as $action) 
    {
      if (!property_exists($action, 'parameterDefinitions'))
      {
        continue;
      }
      
      foreach($action->parameterDefinitions as $parameterDefinition)
      {
        $default     = property_exists($parameterDefinition, 'defaultParameterValue')
          ? $parameterDefinition->defaultParameterValue->value
          : null;
        $description = property_exists($parameterDefinition, 'description')
          ? utf8_decode($parameterDefinition->description)
          : null;
        $choices     = property_exists($parameterDefinition, 'choices')
          ? $parameterDefinition->choices
          : null;
  
        $parameters[$parameterDefinition->name] = array(
          'default'     => $default,
          'choices'     => $choices,
          'description' => $description,
        );
      }
    }
    
    return $parameters;
  }

  /**
   * @return string
   *
   * @throws RuntimeException
   */
  public function retrieveXmlConfigAsString()
  {
    return $this->jenkins->retrieveXmlConfigAsString($this->getName());
  }

  /**
   * @return DOMDocument
   */
  public function retrieveXmlConfigAsDomDocument()
  {
    $document = new DOMDocument;
    $document->loadXML($this->retrieveXmlConfigAsString());
    return $document;
  }

  /**
   * @return \Jenkins
   */
  public function getJenkins()
  {
    return $this->jenkins;
  }

  /**
   * @param \Jenkins $jenkins
   *
   * @return Jenkins_Job
   */
  public function setJenkins(Jenkins $jenkins)
  {
    $this->jenkins = $jenkins;

    return $this;
  }

}
