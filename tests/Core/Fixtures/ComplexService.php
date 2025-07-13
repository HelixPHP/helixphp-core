<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core\Fixtures;

class ComplexService
{
    public ServiceInterface $serviceInterface;
    public ConfigService $configService;

    public function __construct(ServiceInterface $serviceInterface, ConfigService $configService)
    {
        $this->serviceInterface = $serviceInterface;
        $this->configService = $configService;
    }
}
