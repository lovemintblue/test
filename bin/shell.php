<?php

include_once dirname(__FILE__).'/../vendor/autoload.php';

use App\CliApplication;
$application = new CliApplication();
$application->run();
