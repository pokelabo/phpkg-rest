<?php

error_reporting(E_ALL | E_STRICT);
require __DIR__ . '/../vendor/autoload.php';

$loader = new Apix\Autoloader;
$loader->prepend(realpath(__DIR__));
$loader->register(true);

use pokelabo\config\ConfigRepository;
ConfigRepository::addConfigDir(realpath(__DIR__ . '/config'));