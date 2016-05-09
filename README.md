Jenkins PHP API
===============


Jenkins PHP API is a set of classes designed to interact with Jenkins CI using its API.

Installation
------------

The recommended way to install Jenkins PHP API is through [Composer](http://getcomposer.org).

```bash
curl -sS https://getcomposer.org/installer | php
```

Then, run the Composer command to install the latest version:

```bash
composer.phar require jenkins-khan/jenkins-api
```


Basic Usage
-----------


Before anything, you need to instantiate the client :


```php
    $jenkins = new \JenkinsKhan\Jenkins('http://host.org:8080');
```

If your Jenkins needs authentication, you need to pass a URL like this : `'http://user:token@host.org:8080'`.


Here are some examples of how to use it:


Get the color of the job
------------------------

```php
    $job = $jenkins->getJob("dev2-pull");
    var_dump($job->getColor());
    //string(4) "blue"
```


Launch a Job
------------

```php
    $job = $jenkins->launchJob("clone-deploy");
    var_dump($job);
    // bool(true) if successful or throws a RuntimeException
```


List the jobs of a given view
-----------------------------

```php
    $view = $jenkins->getView('madb_deploy');
    foreach ($view->getJobs() as $job) {
      var_dump($job->getName());
    }
    //string(13) "altlinux-pull"
    //string(8) "dev-pull"
    //string(9) "dev2-pull"
    //string(11) "fedora-pull"
```

List builds and their status
----------------------------

```php
    $job = $jenkins->getJob('dev2-pull');
    foreach ($job->getBuilds() as $build) {
      var_dump($build->getNumber());
      var_dump($build->getResult());
    }
    //int(122)
    //string(7) "SUCCESS"
    //int(121)
    //string(7) "FAILURE"
```


Check if Jenkins is available
-----------------------------

```php
    var_dump($jenkins->isAvailable());
    //bool(true);
```

For more information, see the [Jenkins API](https://wiki.jenkins-ci.org/display/JENKINS/Remote+access+API).


Coding standards
----------------

This projects follows PSR-0, PSR-1, PSR-2, PSR-4
