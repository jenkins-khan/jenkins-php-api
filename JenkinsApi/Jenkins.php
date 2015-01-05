<?php
namespace JenkinsApi;

use DOMDocument;
use InvalidArgumentException;
use JenkinsApi\Jenkins\Build;
use JenkinsApi\Jenkins\Executor;
use JenkinsApi\Jenkins\Job;
use JenkinsApi\Jenkins\JobQueue;
use JenkinsApi\Jenkins\Node;
use JenkinsApi\Jenkins\Queue;
use JenkinsApi\Jenkins\TestReport;
use JenkinsApi\Jenkins\View;
use RuntimeException;
use stdClass;

/**
 * Base class to control Jenkins
 *
 * @package    Jenkins
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Jenkins
{
    /**
     * @var string
     */
    private $_baseUrl;

    /**
     * @var stdClass
     */
    protected $_jenkins = null;

    /**
     * Whether or not to retrieve and send anti-CSRF crumb tokens
     * with each request
     *
     * Defaults to false for backwards compatibility
     *
     * @var boolean
     */
    private $_crumbsEnabled = false;

    /**
     * The anti-CSRF crumb to use for each request
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     *
     * @var string
     */
    private $_crumb;

    /**
     * The header to use for sending anti-CSRF crumbs
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     *
     * @var string
     */
    private $_crumbRequestField;

    /**
     * @param string $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }

    /**
     * Enable the use of anti-CSRF crumbs on requests
     *
     * @return void
     */
    public function enableCrumbs()
    {
        $this->_crumbsEnabled = true;

        $crumbResult = $this->requestCrumb();

        if (!$crumbResult || !is_object($crumbResult)) {
            $this->_crumbsEnabled = false;

            return;
        }

        $this->_crumb = $crumbResult->crumb;
        $this->_crumbRequestField = $crumbResult->crumbRequestField;
    }

    /**
     * Disable the use of anti-CSRF crumbs on requests
     *
     * @return void
     */
    public function disableCrumbs()
    {
        $this->_crumbsEnabled = false;
    }

    /**
     * Get the status of anti-CSRF crumbs
     *
     * @return boolean Whether or not crumbs have been enabled
     */
    public function areCrumbsEnabled()
    {
        return $this->_crumbsEnabled;
    }

    public function requestCrumb()
    {
        $url = sprintf('%s/crumbIssuer/api/json', $this->_baseUrl);

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException('Error getting csrf crumb');
        }

        $crumbResult = json_decode($ret);

        if (!$crumbResult instanceof stdClass) {
            throw new RuntimeException('Error during json_decode of csrf crumb');
        }

        return $crumbResult;
    }

    public function getCrumbHeader()
    {
        return "$this->_crumbRequestField: $this->_crumb";
    }

    /**
     * @return boolean
     */
    public function isAvailable()
    {
        $curl = curl_init($this->_baseUrl . '/api/json');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($curl);

        if (curl_errno($curl)) {
            return false;
        } else {
            try {
                $this->getQueue();
            } catch (RuntimeException $e) {
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
        if (null !== $this->_jenkins) {
            return;
        }

        $curl = curl_init($this->_baseUrl . '/api/json');

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error during getting list of jobs on %s', $this->_baseUrl));
        }
        $this->_jenkins = json_decode($ret);
        if (!$this->_jenkins instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
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
        foreach ($this->_jenkins->jobs as $job) {
            $jobs[$job->name] = array(
                'name' => $job->name
            );
        }

        return $jobs;
    }

    /**
     * @return Job[]
     */
    public function getJobs()
    {
        $this->initialize();

        $jobs = array();
        foreach ($this->_jenkins->jobs as $job) {
            $jobs[$job->name] = $this->getJob($job->name);
        }

        return $jobs;

    }

    /**
     * @param string $node
     *
     * @return array
     * @throws RuntimeException
     */
    public function getExecutors($node = '(master)')
    {
        $this->initialize();

        $executors = array();
        for ($i = 0; $i < $this->_jenkins->numExecutors; $i++) {
            $url = sprintf('%s/computer/%s/executors/%s/api/json', $this->_baseUrl, $node, $i);
            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $ret = curl_exec($curl);

            if (curl_errno($curl)) {
                throw new RuntimeException(
                    sprintf(
                        'Error during getting information for executors[%s@%s] on %s', $i, $node, $this->_baseUrl
                    )
                );
            }
            $infos = json_decode($ret);
            if (!$infos instanceof stdClass) {
                throw new RuntimeException('Error during json_decode');
            }

            $executors[] = new Executor($infos, $node, $this);
        }

        return $executors;
    }

    /**
     * @param string $jobName
     * @param array  $parameters
     *
     * @return bool
     */
    public function launchJob($jobName, $parameters = array())
    {
        if (0 === count($parameters)) {
            $url = sprintf('%s/job/%s/build', $this->_baseUrl, $jobName);
        } else {
            $url = sprintf('%s/job/%s/buildWithParameters', $this->_baseUrl, $jobName);
        }

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error trying to launch job "%s" (%s)', $parameters['name'], $url));
        }

        return true;
    }

    /**
     * @param $jobName
     *
     * @return Job
     * @throws RuntimeException
     */
    public function getJob($jobName)
    {
        $url = sprintf('%s/job/%s/api/json', $this->_baseUrl, $jobName);
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $response_info = curl_getinfo($curl);

        if (200 != $response_info['http_code']) {
            return false;
        }

        if (curl_errno($curl)) {
            throw new RuntimeException(
                sprintf('Error during getting information for job %s on %s', $jobName, $this->_baseUrl)
            );
        }
        $infos = json_decode($ret);
        if (!$infos instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }

        return new Job($infos, $this);
    }

    /**
     * @param string $jobName
     *
     * @return void
     */
    public function deleteJob($jobName)
    {
        $url = sprintf('%s/job/%s/doDelete', $this->_baseUrl, $jobName);
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error deleting job %s on %s', $jobName, $this->_baseUrl));
        }
    }

    /**
     * @return Queue
     * @throws RuntimeException
     */
    public function getQueue()
    {
        $url = sprintf('%s/queue/api/json', $this->_baseUrl);
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error during getting information for queue on %s', $this->_baseUrl));
        }
        $infos = json_decode($ret);
        if (!$infos instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }

        return new Queue($infos, $this);
    }

    /**
     * @return View[]
     */
    public function getViews()
    {
        $this->initialize();

        $views = array();
        foreach ($this->_jenkins->views as $view) {
            $views[] = $this->getView($view->name);
        }

        return $views;
    }

    /**
     * @return View|null
     */
    public function getPrimaryView()
    {
        $this->initialize();
        $primaryView = null;

        if (property_exists($this->_jenkins, 'primaryView')) {
            $primaryView = $this->getView($this->_jenkins->primaryView->name);
        }

        return $primaryView;
    }


    /**
     * @param string $viewName
     *
     * @return View
     * @throws RuntimeException
     */
    public function getView($viewName)
    {
        $url = sprintf('%s/view/%s/api/json', $this->_baseUrl, rawurlencode($viewName));
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(
                sprintf('Error during getting information for view %s on %s', $viewName, $this->_baseUrl)
            );
        }
        $infos = json_decode($ret);
        if (!$infos instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }

        return new View($infos, $this);
    }


    /**
     * @param        $job
     * @param        $buildId
     * @param string $tree
     *
     * @return Build
     * @throws RuntimeException
     */
    public function getBuild(
        $job, $buildId,
        $tree = 'actions[parameters,parameters[name,value]],result,duration,timestamp,number,url,estimatedDuration,builtOn')
    {
        if ($tree !== null) {
            $tree = sprintf('?tree=%s', $tree);
        }
        $url = sprintf('%s/job/%s/%d/api/json%s', $this->_baseUrl, $job, $buildId, $tree);
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(
                sprintf('Error during getting information for build %s#%d on %s', $job, $buildId, $this->_baseUrl)
            );
        }
        $infos = json_decode($ret);

        if (!$infos instanceof stdClass) {
            return null;
        }

        return new Build($infos, $this);
    }

    /**
     * @param string $job
     * @param int    $buildId
     *
     * @return null|string
     */
    public function getUrlBuild($job, $buildId)
    {
        return (null === $buildId) ?
            $this->getUrlJob($job)
            : sprintf('%s/job/%s/%d', $this->_baseUrl, $job, $buildId);
    }

    /**
     * @param string $nodeName
     *
     * @return Node
     * @throws RuntimeException
     */
    public function getNode($nodeName)
    {
        $url = sprintf('%s/computer/%s/api/json', $this->_baseUrl, $nodeName);
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(
                sprintf('Error during getting information for node %s on %s', $nodeName, $this->_baseUrl)
            );
        }
        $infos = json_decode($ret);

        if (!$infos instanceof stdClass) {
            return null;
        }

        return new Node($infos, $this);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @param string $job
     *
     * @return string
     */
    public function getUrlJob($job)
    {
        return sprintf('%s/job/%s', $this->_baseUrl, $job);
    }

    /**
     * getUrlView
     *
     * @param string $view
     *
     * @return string
     */
    public function getUrlView($view)
    {
        return sprintf('%s/view/%s', $this->_baseUrl, $view);
    }

    /**
     * @param string $jobname
     *
     * @return string
     *
     * @deprecated use getJobConfig instead
     *
     * @throws RuntimeException
     */
    public function retrieveXmlConfigAsString($jobname)
    {
        return $this->getJobConfig($jobname);
    }

    /**
     * @param string      $jobname
     * @param DomDocument $document
     *
     * @deprecated use setJobConfig instead
     */
    public function setConfigFromDomDocument($jobname, DomDocument $document)
    {
        $this->setJobConfig($jobname, $document->saveXML());
    }

    /**
     * @param string $jobname
     * @param string $xmlConfiguration
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function createJob($jobname, $xmlConfiguration)
    {
        $url = sprintf('%s/createItem?name=%s', $this->_baseUrl, $jobname);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlConfiguration);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $headers = array('Content-Type: text/xml');

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);

        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) {
            throw new InvalidArgumentException(sprintf('Job %s already exists', $jobname));
        }
        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error creating job %s', $jobname));
        }
    }

    /**
     * @param string       $jobname
     * @param string|array $configuration
     *
     */
    public function setJobConfig($jobname, $configuration)
    {
        $url = sprintf('%s/job/%s/config.xml', $this->_baseUrl, $jobname);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $configuration);

        $headers = array('Content-Type: text/xml');

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_exec($curl);
        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error during setting configuration for job %s', $jobname));
        }
    }

    /**
     * @param string $jobname
     *
     * @return string
     */
    public function getJobConfig($jobname)
    {
        $url = sprintf('%s/job/%s/config.xml', $this->_baseUrl, $jobname);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error during getting configuation for job %s', $jobname));
        }
        return $ret;
    }

    /**
     * @param Executor $executor
     *
     * @throws RuntimeException
     */
    public function stopExecutor(Executor $executor)
    {
        $url = sprintf(
            '%s/computer/%s/executors/%s/stop', $this->_baseUrl, $executor->getNode(), $executor->getNumber()
        );

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_exec($curl);
        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error durring stopping executor #%s', $executor->getNumber()));
        }
    }

    /**
     * @param JobQueue $queue
     *
     * @throws RuntimeException
     * @return void
     */
    public function cancelQueue(JobQueue $queue)
    {
        $url = sprintf('%s/queue/item/%s/cancelQueue', $this->_baseUrl, $queue->getId());

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_exec($curl);
        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error durring stopping job queue #%s', $queue->getId()));
        }
    }

    /**
     * @param string $nodeName
     *
     * @throws RuntimeException
     * @return void
     */
    public function toggleOfflineNode($nodeName)
    {
        $url = sprintf('%s/computer/%s/toggleOffline', $this->_baseUrl, $nodeName);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_exec($curl);
        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error marking %s offline', $nodeName));
        }
    }

    /**
     * @param string $nodeName
     *
     * @throws RuntimeException
     * @return void
     */
    public function deleteNode($nodeName)
    {
        $url = sprintf('%s/computer/%s/doDelete', $this->_baseUrl, $nodeName);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);

        $headers = array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_exec($curl);
        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error deleting %s', $nodeName));
        }
    }

    /**
     * @param string $jobname
     * @param string $buildNumber
     *
     * @return string
     */
    public function getConsoleTextBuild($jobname, $buildNumber)
    {
        $url = sprintf('%s/job/%s/%s/consoleText', $this->_baseUrl, $jobname, $buildNumber);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        return curl_exec($curl);
    }

    /**
     * @param string $jobName
     * @param string $buildId
     *
     * @return array
     */
    public function getTestReport($jobName, $buildId)
    {
        $url = sprintf('%s/job/%s/%d/testReport/api/json', $this->_baseUrl, $jobName, $buildId);
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $errorMessage = sprintf(
            'Error during getting information for build %s#%d on %s', $jobName, $buildId, $this->_baseUrl
        );

        if (curl_errno($curl)) {
            throw new RuntimeException($errorMessage);
        }
        $infos = json_decode($ret);

        if (!$infos instanceof stdClass) {
            throw new RuntimeException($errorMessage);
        }

        return new TestReport($this, $infos, $jobName, $buildId);
    }

    /**
     * Returns the content of a page according to the jenkins base url.
     * Usefull if you use jenkins plugins that provides specific APIs.
     * (e.g. "/cloud/ec2-us-east-1/provision")
     *
     * @param string $uri
     * @param array  $curlOptions
     *
     * @return string
     */
    public function execute($uri, array $curlOptions)
    {
        $url = $this->_baseUrl . '/' . $uri;
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOptions);
        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error calling "%s"', $url));
        }
        return $ret;
    }

    /**
     * @return Node[]
     */
    public function getNodes()
    {
        $return = $this->execute(
            '/computer/api/json', array(
                CURLOPT_RETURNTRANSFER => 1,
            )
        );
        $infos = json_decode($return);
        if (!$infos instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }
        $nodes = array();
        foreach ($infos->computer as $node) {
            $nodes[] = $this->getNode($node->displayName);
        }
        return $nodes;
    }

    /**
     * @param string $nodeName
     *
     * @return string
     */
    public function getNodeConfiguration($nodeName)
    {
        return $this->execute(
            sprintf('/computer/%s/config.xml', $nodeName), array(
                CURLOPT_RETURNTRANSFER => 1,
            )
        );
    }
}
