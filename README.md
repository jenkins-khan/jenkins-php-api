Jenkins PHP API
===============


Jenkins PHP API is a set of classes designed to interract with Jenkins CI using it's API.

Before anything, you need to instanciate the client :


```php
    require_once('Autoload.php');
    Jenkins_Autoloader::register();
    $jenkins = new Jenkins('http://host.org:8080');
```

If your jenkins need authentication, you need to pass and URL like this : `'http://user:token@host.org:8080'`.


Here is some examples of how to use it :


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
```


List the jobs of view
---------------------

```php
    $view = $jenkins->getView('madb_deploy');
    //string(4) "blue"
    foreach ($view->getJobs() as $job) {
      var_dump($job->getName());
    }
    //string(13) "altlinux-pull"
    //string(8) "dev-pull"
    //string(9) "dev2-pull"
    //string(11) "fedora-pull"
```

List Builds and their status
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


Check if jenkins is available
-----------------------------

```php
    var_dump($jenkins->isAvailable());
    //bool(true);
```

More information about Jenkins API could be found [here](https://wiki.jenkins-ci.org/display/JENKINS/Remote+access+API)

