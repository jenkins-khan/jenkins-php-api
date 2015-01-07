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


use JenkinsApi\Jenkins\Job;
use JenkinsApi\Jenkins\Queue;
use RuntimeException;
use stdClass;

class Jenkins
{
    /**
     * @var string
     */
    private $_baseUrl;

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
    public function __construct($baseUrl)
    {
        $this->_baseUrl = $baseUrl . (substr($baseUrl, -1) === '/') ? '' : '/';
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
        $data = $this->get('queue/api/json');

        return new Queue($data, $this);
    }

    /**
     * @param string $url
     * @param int    $depth
     *
     * @return stdClass
     */
    public function get($url, $depth = 1)
    {
        $url = sprintf('%s' . $url . '?depth=' . $depth, $this->_baseUrl);
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $response_info = curl_getinfo($curl);

        if (200 != $response_info['http_code']) {
            throw new RuntimeException(sprintf('Error during getting information from url %s', $url));
        }

        if (curl_errno($curl)) {
            throw new RuntimeException(sprintf('Error during getting information from url %s', $url));
        }
        $data = json_decode($ret);
        if (!$data instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }

        return $data;
    }

    /**
     * @param string $url
     * @param array  $parameters
     *
     * @return bool
     */
    public function post($url, $parameters = [])
    {
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
}