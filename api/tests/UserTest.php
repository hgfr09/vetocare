<?php

namespace App\Tests;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserTest extends AbstractApiTestCase
{
    // Creation
    public function testCreateValidUser(): void
    {
        $reponse = static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => "Daniel"
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame("user@test.com", $reponse->toArray()['email']);
    }

    public function testDefaultUserRoleIsRoleVeto(): void
    {
        $reponse = static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => "Daniel"
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertArraySubset(['ROLE_VETO'], $reponse->toArray()['roles']);
    }

    // Update
    public function testUpdateUserWithoutPasswordKeepsCurrentPassword(): void
    {
        $plainPassword = "123456";

        static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => $plainPassword
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => "user@test.com"]);

        static::createClient()->request('PATCH', "/api/users/{$user->getId()}", [
            "headers" => [
                "Accept" => "application/ld+json",
                "Content-Type" => "application/merge-patch+json"
            ],
            "json" => [
                "email" => "user-modified@test.com",
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $modifiedUser = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => "user-modified@test.com"]);
        $this->assertTrue(static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($modifiedUser, $plainPassword));
    }

    // Validation 
    #[DataProvider('invalidUserProvider')]
    public function testCannotCreateInvalidUser(string $email, string $password, string $errorMessage): void
    {
        static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => $email,
                "plainPassword" => $password
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['description' => $errorMessage]);
    }

    public static function invalidUserProvider(): array
    {
        // email, password, error message
        return [
            'Password is too short' => ['test@test.com', 'dani', 'plainPassword: Le mot de passe doit avoir au moins 6 caractères.'],
            'Email is not valid' => ['test@test', 'Daniel', "email: L'email est invalide."],
            'Email is required' => ['', 'Daniel', "email: L'email est obligatoire."],
        ];
    }

    public function testCannotCreateUsersWithSameEmail(): void
    {
        static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => "Daniel"
            ]
        ]);
        static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => "Daniel125"
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(["description" => "email: Cet email existe déjà."]);
    }

    // Security
    public function testPasswordIsHashedInDatabase(): void
    {
        $plainPassword = "123456";
        static::createClient()->request('POST', '/api/users', [
            'headers' => self::$HEADERS_WRITE,
            'json' => [
                "email" => "user@test.com",
                "plainPassword" => $plainPassword
            ]
        ]);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(["email" => "user@test.com"]);

        $this->assertNotSame($plainPassword, $user->getPassword());
        $this->assertTrue(static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, $plainPassword));
    }

    public function testPlainPasswordIsNotIncludedInTheResponse(): void
    {
        $reponse = static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => "Daniel"
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertArrayNotHasKey("plainPassword", $reponse->toArray());
    }
}
