<?php

declare(strict_types=1);

use Medas\Encryption\EncryptionPackage;
use Medas\ObjectInstantiator\{ObjectInstantiator, ObjectInstantiatorPackage};
use Medas\ServiceManager\{ServiceConfig, ServiceManager};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig(ObjectInstantiator::class);

    $config->addPackages([
        EncryptionPackage::instance(),
        ObjectInstantiatorPackage::instance(),
    ]);

    return $config;
});
