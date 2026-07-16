<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

abstract class AbstractApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected static array $HEADERS_READ = [
        "Accept" => "application/ld+json"
    ];
    protected static array $HEADERS_WRITE = [
        "Accept" => "application/ld+json",
        "Content-Type" => "application/ld+json"
    ];
}
