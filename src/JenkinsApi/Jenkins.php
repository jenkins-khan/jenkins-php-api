<?php
/**
 * JenkinsNew.php
 *
 * @package    Atlas
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @copyright  2013 Jungheinrich AG
 * @license    Proprietary license
 * @version    $Id$
 */

namespace JenkinsApi;

use InvalidArgumentException;
use JenkinsApi\Item\Build;
use JenkinsApi\Item\Executor;
use JenkinsApi\Item\Job;
use JenkinsApi\Item\LastBuild;
use JenkinsApi\Item\Node;
use JenkinsApi\Item\Queue;
use JenkinsApi\Item\View;
use RuntimeException;
use stdClass;

/**
 * Wrapper for general
 *
 * @package    JenkinsApi
 * @author     Christopher Biel <christopher.biel@jungheinrich.de>
 * @version    $Id$
 */
class Jenkins
{
    const FORMAT_OBJECT = 'asObject';
    const FORMAT_XML = 'asXml';

    /**
     * @var bool
     */
    private $_verbose = false;

    /**
     * @var string
     */
    private $_baseUrl;

    /**
     * @var string
     */
    private $_username;

    /**
     * @var string
     */
    private $_password;

    /**
     * @var string
     */
    private $_urlExtension = '/api/json';

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
    public function __construct($baseUrl, $username='', $password='')
    {
        $this->_baseUrl  = $baseUrl . ((substr($baseUrl, -1) === '/') ? '' : '/');
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * @param string|Job $jobName
     * @param int|string $buildNumber
     *
     * @return Build
     */
    public function getBuild($jobName, $buildNumber)
    {
        return new Build($buildNumber, $jobName, $this);
    }

    /**
     * @param string $jobName
     *
     * @return Job
     */
    public function getJob($jobName)
    {
        return new Job($jobName, $this);
    }

    /**
     * @return Job[]
     */
    public function getJobs()
    {
        $data = $this->get('api/json');

        $jobs = array();
        foreach ($data->jobs as $job) {
            $jobs[$job->name] = $this->getJob($job->name);
        }

        return $jobs;
    }

    /**
     * @return Queue
     * @throws RuntimeException
     */
    public function getQueue()
    {
        return new Queue($this);
    }

    /**
     * @param string $url
     * @param int    $depth
     * @param array  $params
     * @param array  $curlOpts
     * @param bool   $raw
     *
     * @throws RuntimeException
     * @return stdClass
     */
    public function get($url, $depth = 1, $params = array(), array $curlOpts = [], $raw = false)
    {
//        $url = str_replace(' ', '%20', sprintf('%s' . $url . '?depth=' . $depth, $this->_baseUrl));
        $url = sprintf('%s', $this->_baseUrl) . $url . '?depth=' . $depth;
        if ($params) {
            foreach ($params as $key => $val) {
                $url .= '&' . $key . '=' . $val;
            }
        }
        $curl = curl_init($url);
        if ($curlOpts) {
            curl_setopt_array($curl, $curlOpts);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($this->_username) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_username.":".$this->_password);
        }

        $ret = curl_exec($curl);

        $response_info = curl_getinfo($curl);

        if (200 != $response_info['http_code']) {
            throw new RuntimeException(
                sprintf(
                    'Error during getting information from url %s (Response: %s)', $url, $response_info['http_code']
                )
            );
        }

        if (curl_errno($curl)) {
            throw new RuntimeException(
                sprintf('Error during getting information from url %s (%s)', $url, curl_error($curl))
            );
        }
        if ($raw) {
            return $ret;
        }
        $data = json_decode($ret);
        if (!$data instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }

        return $data;
    }

    /**
     * @param string       $url
     * @param array|string $parameters
     * @param array        $curlOpts
     *
     * @throws RuntimeException
     * @return bool
     */
    public function post($url, $parameters = [], array $curlOpts = [])
    {
        $url = sprintf('%s', $this->_baseUrl) . $url;

        $curl = curl_init($url);
        if ($curlOpts) {
            curl_setopt_array($curl, $curlOpts);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        if (is_array($parameters)) {
            $parameters = http_build_query($parameters);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);

        if ($this->_username) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_username.":".$this->_password);
        }

        $headers = (isset($curlOpts[CURLOPT_HTTPHEADER])) ? $curlOpts[CURLOPT_HTTPHEADER] : array();

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $return = curl_exec($curl);

        return (curl_errno($curl)) ?: $return;
    }

    /**
     * @return boolean
     */
    public function isAvailable()
    {
        $curl = curl_init($this->_baseUrl . '/api/json');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($this->_username) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_username.":".$this->_password);
        }

        curl_exec($curl);

        if (curl_errno($curl)) {
            return false;
        } else {
            try {
                $this->getQueue();
            } catch (RuntimeException $e) {
                return false;
            }
        }

        return true;
    }

    public function getCrumbHeader()
    {
        return "$this->_crumbRequestField: $this->_crumb";
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

        if ($this->_username) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_username.":".$this->_password);
        }

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
     * Get the currently building jobs
     *
     * @param string $outputFormat One of the FORMAT_* constants
     *
     * @return Item\Job[]
     */
    public function getCurrentlyBuildingJobs($outputFormat = self::FORMAT_OBJECT)
    {
        $url = sprintf("%s", $this->_baseUrl)
            . "/api/xml?tree=jobs[name,url,color]&xpath=/hudson/job[ends-with(color/text(),%22_anime%22)]&wrapper=jobs";
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($this->_username) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_username.":".$this->_password);
        }

        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException(
                sprintf(
                    'Error during getting all currently building jobs on %s (%s)', $this->_baseUrl, curl_error($curl)
                )
            );
        }

        $xml = simplexml_load_string($ret);
        $builds = $xml->xpath('/jobs');

        switch ($outputFormat) {
            case self::FORMAT_OBJECT:
                $buildingJobs = [];
                foreach ($builds as $build) {
                    $buildingJobs[] = new Job($build->job->name, $this);
                }
                return $buildingJobs;
            case self::FORMAT_XML:
                return $builds;
            default:
                throw new InvalidArgumentException('Output format "' . $outputFormat . '" is unknown!');
        }
    }

    /**
     * Get the last builds from the currently building jobs
     *
     * @return LastBuild[]
     */
    public function getLastBuildsFromCurrentlyBuildingJobs()
    {
        $builds = $this->getCurrentlyBuildingJobs(true)[0];
        $lastBuilds = [];
        foreach ($builds->job as $job) {
            $lastBuilds[] = new LastBuild($job->name, $this);
        }

        return $lastBuilds;
    }

    /**
     * @return View[]
     */
    public function getViews()
    {
        $data = $this->get('api/json');
        $views = array();
        foreach ($data->views as $view) {
            $views[] = $this->getView($view->name);
        }
        return $views;
    }

    /**
     * @return View|null
     */
    public function getPrimaryView()
    {
        $data = $this->get('api/json');

        $primaryView = null;
        if (property_exists($data, 'primaryView')) {
            $primaryView = $this->getView($data->primaryView->name);
        }

        return $primaryView;
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
        $url = sprintf('%s/createItem?name=%s', $this->getBaseUrl(), $jobname);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlConfiguration);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($this->_username) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_username.":".$this->_password);
        }

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
            throw new RuntimeException(sprintf('Error creating job %s (%s)', $jobname, curl_error($curl)));
        }
    }

    /**
     * @return Node[]
     */
    public function getNodes()
    {
        $data = json_decode($this->get('computer/api/json'));
        $nodes = array();
        foreach ($data->computer as $node) {
            $nodes[] = new Node($node->displayName, $this);
        }
        return $nodes;
    }

    /**
     * @return Executor[]
     */
    public function getExecutors()
    {
        $executors = array();
        foreach ($this->getNodes() as $node) {
            $executors = array_merge($executors, $node->getExecutors());
        }

        return $executors;
    }

    /**
     * Go into prepare shutdown mode.
     * This prevents new jobs beeing started
     */
    public function prepareShutdown()
    {
        $this->post('quietDown');
    }

    /**
     * Exit prepare shutdown mode
     * This allows jobs beeing started after shutdown prepare (but before actual restart)
     */
    public function cancelPrepareShutdown()
    {
        $this->post('cancelQuietDown');
    }

    /**
     * @param string $viewName
     *
     * @return View
     * @throws RuntimeException
     */
    public function getView($viewName)
    {
        return new View($viewName, $this);
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
     * @return string
     */
    public function getUrlExtension()
    {
        return $this->_urlExtension;
    }

    /**
     * @param string $urlExtension
     */
    public function setUrlExtension($urlExtension)
    {
        $this->_urlExtension = $urlExtension;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }

    /**
     * @return boolean
     */
    public function isVerbose()
    {
        return $this->_verbose;
    }

    /**
     * @param boolean $verbose
     */
    public function setVerbose($verbose)
    {
        $this->_verbose = $verbose;
    }
}
