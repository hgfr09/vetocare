<?php

namespace App\Tests\Functional;

use App\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

final class SecurityTest extends AbstractApiTestCase
{
    public function testGuestCanAccessLoginPageWithValidData(): void
    {
        UserFactory::createOne([
            'email' => 'test@test.com',
            'plainPassword' => '123456'
        ]);

        $this->browser()->post('/api/login_check', [
            'json' => [
                'email' => 'test@test.com',
                'password' => '123456'
            ]
        ])
            ->assertStatus(Response::HTTP_OK)
            ->assertContains('token');
    }

    public function testCannotAccessLoginPageWithInvalidData(): void
    {
        $this->browser()->post('/api/login_check', [
            'json' => [
                'email' => 'test@test.com',
                'password' => '123456'
            ]
        ])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    #[DataProvider('protectedRoutesProvider')]
    public function testGuestCannotAccessProtectedRoutes(string $method, string $url): void
    {
        $this->browserAnonymous()->request($method, $url)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public static function protectedRoutesProvider(): iterable
    {
        yield 'Get animals' => ['GET', '/api/animals'];
        yield 'Get consultations' => ['GET', '/api/consultations'];
        yield 'Get users' => ['GET', '/api/users'];
        yield 'Create animal' => ['POST', '/api/animals'];
        yield 'Create consultation' => ['POST', '/api/consultations'];
        yield 'Create user' => ['POST', '/api/users'];
    }
}
