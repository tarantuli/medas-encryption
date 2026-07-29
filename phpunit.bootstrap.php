<?php

declare(strict_types=1);

use Medas\Encryption\EncryptionPackage;
use Medas\ObjectInstantiator\{ObjectInstantiator, ObjectInstantiatorPackage};
use Medas\ServiceManager\{ServiceConfigBuilder, ServiceManager};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfigBuilder {
    $config = new ServiceConfigBuilder(ObjectInstantiator::class);

    $config->addPackages([
        EncryptionPackage::instance(),
        ObjectInstantiatorPackage::instance(),
    ]);

    return $config;
});
