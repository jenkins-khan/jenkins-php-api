<?php

class Jenkins
{

  /**
   * @var string
   */
  private $baseUrl;

  /**
   * @var null
   */
  private $jenkins = null;

  /**
   * @param string $baseUrl
   */
  public function __construct($baseUrl)
  {
    $this->baseUrl = $baseUrl;
  }

  /**
   * @return boolean
   */
  public function isAvailable()
  {
    $curl = curl_init($this->baseUrl . '/api/json');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($curl);

    if (curl_errno($curl))
    {
      return false;
    }
    else
    {
      try
      {
        $this->getQueue();
      }
      catch(RunTimeException $e)
      {
        //en cours de lancement de jenkins, on devrait passer par là
        return false;
      }
    }

    return true;
  }

  /**
   * @return void
   * @throws RuntimeException
   */
  private function initialize()
  {
    if (null !== $this->jenkins)
    {
      return;
    }

    $curl = curl_init($this->baseUrl . '/api/json');

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($curl);

    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error during getting list of jobs on %s', $this->baseUrl));
    }
    $this->jenkins = json_decode($ret);
    if (!$this->jenkins instanceof stdClass)
    {
      throw new RunTimeException('Error during json_decode');
    }
  }

  /**
   * @throws RuntimeException
   * @return array
   */
  public function getAllJobs()
  {
    $this->initialize();

    $jobs = array();
    foreach ($this->jenkins->jobs as $job)
    {
      $jobs[$job->name] = array(
        'name' => $job->name
      );
    }

    return $jobs;
  }

  /**
   * @param string $computer
   * @return array
   * @throws RuntimeException
   */
  public function getExecutors($computer = '(master)')
  {
    $this->initialize();

    $executors = array();
    for ($i = 0; $i <  $this->jenkins->numExecutors ; $i++)
    {
      $url = sprintf('%s/computer/%s/executors/%s/api/json', $this->baseUrl, $computer, $i);
      $curl = curl_init($url);

      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $ret = curl_exec($curl);

      if (curl_errno($curl))
      {
        throw new RuntimeException(sprintf('Error during getting information for executors[%s@%s] on %s', $i, $computer, $this->baseUrl));
      }
      $infos = json_decode($ret);
      if (!$infos instanceof stdClass)
      {
        throw new RunTimeException('Error during json_decode');
      }

      $executors[] = new Jenkins_Executor($infos, $computer, $this);
    }

    return $executors;
  }

  /**
   * @param array $extraParameters
   * @return bool
   * @throws RuntimeException
   */
  public function launchJob($jobName, $parameters = array())
  {
    if (0 === count($parameters))
    {
      $url = sprintf('%s/job/%s/build', $this->baseUrl, $jobName);
    }
    else
    {
      $url = sprintf('%s/job/%s/buildWithParameters', $this->baseUrl, $jobName);
    }

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));

    curl_exec($curl);

    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error trying to launch job "%s" (%s)', $parameters['name'], $url));
    }

    return true;
  }

  /**
   * @param $jobName
   * @throws RuntimeException
   */
  public function getJob($jobName)
  {
    $url = sprintf('%s/job/%s/api/json', $this->baseUrl, $jobName);
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($curl);

    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error during getting information for job %s on %s', $jobName, $this->baseUrl));
    }
    $infos = json_decode($ret);
    if (!$infos instanceof stdClass)
    {
      throw new RunTimeException('Error during json_decode');
    }

    return new Jenkins_Job($infos, $this);
  }

  /**
   * @return Jenkins_Queue
   * @throws RuntimeException
   */
  public function getQueue()
  {
    $url = sprintf('%s/queue/api/json', $this->baseUrl);
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($curl);

    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error during getting information for queue on %s', $this->baseUrl));
    }
    $infos = json_decode($ret);
    if (!$infos instanceof stdClass)
    {
      throw new RunTimeException('Error during json_decode');
    }

    return new Jenkins_Queue($infos, $this);
  }

  /**
   * @return Jenkins_View[]
   */
  public function getViews()
  {
    $this->initialize();

    $views = array();
    foreach ($this->jenkins->views as $view)
    {
      $views[] = $this->getView($view->name);
    }

    return $views;
  }

  /**
   * @return Jenkins_View|null
   */
  public function getPrimaryView()
  {
    $this->initialize();
    $primaryView = null;

    if (property_exists($this->jenkins, 'primaryView'))
    {
      $primaryView = $this->getView($this->jenkins->primaryView->name);
    }

    return $primaryView;
  }


  /**
   * @param string $viewName
   * @return Jenkins_View
   * @throws RuntimeException
   */
  public function getView($viewName)
  {
    $url = sprintf('%s/view/%s/api/json', $this->baseUrl, $viewName);
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($curl);

    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error during getting information for view %s on %s', $viewName, $this->baseUrl));
    }
    $infos = json_decode($ret);
    if (!$infos instanceof stdClass)
    {
      throw new RunTimeException('Error during json_decode');
    }

    return new Jenkins_View($infos, $this);
  }


  /**
   * @param        $job
   * @param        $buildId
   * @param string $tree
   *
   * @return Jenkins_Build
   * @throws RuntimeException
   */
  public function getBuild($job, $buildId, $tree = 'actions[parameters,parameters[name,value]],result,duration,timestamp,number,url,estimatedDuration')
  {
    if ($tree !== null)
    {
      $tree = sprintf('?tree=%s', $tree);
    }
    $url = sprintf('%s/job/%s/%d/api/json%s', $this->baseUrl, $job, $buildId, $tree);
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($curl);

    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error during getting information for build %s#%d on %s', $job, $buildId, $this->baseUrl));
    }
    $infos = json_decode($ret);

    if (!$infos instanceof stdClass)
    {
      return null;
    }

    return new Jenkins_Build($infos, $this);
  }

  /**
   * @param string $job
   * @param int $buildId
   * @return null|string
   */
  public function getUrlBuild($job, $buildId)
  {
    return (null === $buildId) ?
        $this->getUrlJob($job)
      : sprintf('%s/job/%s/%d', $this->baseUrl, $job, $buildId)
    ;
  }

  /**
   * @return string
   */
  public function getUrl()
  {
    return $this->baseUrl;
  }

  /**
   * @param string $job
   * @return string
   */
  public function getUrlJob($job)
  {
    return sprintf('%s/job/%s', $this->baseUrl, $job);
  }

  /**
   * getUrlView
   *
   * @param string $view

   * @return string
   */
  public function getUrlView($view)
  {
    return sprintf('%s/view/%s', $this->baseUrl, $view);
  }

  /**
   * @param string $jobname
   *
   * @return string
   *
   * @throws RuntimeException
   */
  public function retrieveXmlConfigAsString($jobname)
  {
    $url  = sprintf('%s/job/%s/config.xml', $this->baseUrl, $jobname);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($curl);
    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error during getting configuation for job %s', $jobname));
    }
    return $ret;
  }

  /**
   * @param string      $jobname
   * @param DomDocument $document
   */
  public function setConfigFromDomDocument($jobname, DomDocument $document)
  {
    $url  = sprintf('%s/job/%s/config.xml', $this->baseUrl, $jobname);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $document->saveXML());
    curl_exec($curl);
    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error during setting configuration for job %s', $jobname));
    }
  }

  /**
   * @param string $jobname
   * @param string $xmlConfiguration
   *
   * @return void
   */
  public function createJob($jobname, $xmlConfiguration)
  {
    $url  = sprintf('%s/createItem?name=%s', $this->baseUrl, $jobname);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlConfiguration);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($curl);
    if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200)
    {
      throw new InvalidArgumentException(sprintf('Job %s already exists', $jobname));
    }
    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error creating job %s', $jobname));
    }
  }

  /**
   * @param Jenkins_Executor $executor
   * @throws RuntimeException
   */
  public function stopExecutor(Jenkins_Executor $executor)
  {
    $url  = sprintf('%s/computer/%s/executors/%s/stop', $this->baseUrl, $executor->getComputer(), $executor->getNumber());

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_exec($curl);
    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error durring stopping executor #%s', $executor->getNumber()));
    }
  }

  /**
   * @param Jenkins_JobQueue $queue
   *
   * @throws RuntimeException
   * @return void
   */
  public function cancelQueue(Jenkins_JobQueue $queue)
  {
    $url  = sprintf('%s/queue/item/%s/cancelQueue', $this->baseUrl, $queue->getId());

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_exec($curl);
    if (curl_errno($curl))
    {
      throw new RuntimeException(sprintf('Error durring stopping job queue #%s', $queue->getId()));
    }
  }



}
