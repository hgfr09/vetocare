<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

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

    protected static array $HEADERS_UPDATE = [
        "Accept"=>"application/ld+json",
        "Content-Type" => "application/merge-patch+json"
    ];

    protected function createAuthenticatedClient(User $user): Client
    {
        self::bootKernel();

        $jwtManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        $token = $jwtManager->create($user);

        return static::createClient([], ['headers' => ['Authorization' => sprintf('Bearer %s', $token)]]);
    }
}
