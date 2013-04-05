<?php

error_reporting(E_ALL | E_STRICT);
require __DIR__ . '/../vendor/autoload.php';

$loader = new Apix\Autoloader;
$loader->prepend(realpath(__DIR__));
$loader->register(true);

use pokelabo\config\ConfigRepository;
ConfigRepository::addConfigDir(realpath(__DIR__ . '/config'));

function createRedisClient() {
    $config = ConfigRepository::load('redis');
    $redis = new Redis();
    $timeout = 2.5;
    $redis->pconnect($config->dig('host'), $config->dig('port'), $config->dig('timeout'));
}

function getGlobalConfig() {
    static $config = null;
    if ($config === null) {
        $etc_dir = realpath(dirname(dirname(__DIR__)) . '/etc');
        $config = json_decode(file_get_contents($etc_dir . '/config.json'), true);
    }
    return $config;
}
