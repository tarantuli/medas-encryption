<?php

declare(strict_types=1);

use Medas\Encryption\EncryptionPackage;
use Medas\ServiceManager\{ServiceConfig, ServiceManager};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig();

    $config->addPackages([
        EncryptionPackage::instance(),
    ]);

    return $config;
});
