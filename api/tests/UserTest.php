<?php

namespace App\Tests;

use App\Factory\UserFactory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
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

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertSame("user@test.com", $reponse->toArray()['email']);
    }

    // Read
    public function testGetSingleUser(): void
    {
        $user = UserFactory::createOne();
        $response = static::createClient()->request('GET', "/api/users/{$user->getId()}", [
            'headers' => self::$HEADERS_READ
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame($user->getId(), $response->toArray()['id']);
    }

    public function testGetAllUsers(): void
    {
        $users = UserFactory::createMany(2);
        $response = static::createClient()->request('GET', '/api/users', [
            'headers' => self::$HEADERS_READ
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['member'];

        $this->assertCount(2, $data);
        $this->assertContains($users[0]->getId(), array_column($data, 'id'));
        $this->assertContains($users[1]->getId(), array_column($data, 'id'));
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

    // Delete
    public function testDeleteUser(): void
    {
        $user = UserFactory::createOne();
        static::createClient()->request('DELETE', "/api/users/{$user->getId()}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        static::createClient()->request('GET', "/api/users/{$user->getId()}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // Validation 
    #[DataProvider('invalidUserProvider')]
    public function testCannotCreateInvalidUser(string $email, string $password, string $propertyPath, string $errorMessage): void
    {
        static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => $email,
                "plainPassword" => $password
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains([
            "violations" => [
                [
                    'propertyPath' => $propertyPath,
                    'message' => $errorMessage
                ]
            ]
        ]);
    }

    public static function invalidUserProvider(): array
    {
        // email, password, propertyPath, error message
        return [
            'Password is too short' => ['test@test.com', 'dani', 'plainPassword', 'Le mot de passe doit avoir au moins 6 caractères.'],
            'Password is required' => ['test@test.com', '',  'plainPassword', 'Le mot de passe est obligatoire.'],
            'Email is not valid' => ['test@test', 'Daniel', 'email', "L'email est invalide."],
            'Email is required' => ['', 'Daniel', 'email', "L'email est obligatoire."],
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

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonContains(["description" => "email: Cet email existe déjà."]);
    }

    // Business Rules
    public function testDefaultUserRoleIsRoleVeto(): void
    {
        $response = static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => "Daniel"
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArraySubset(['ROLE_VETO'], $response->toArray()['roles']);
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

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArrayNotHasKey("plainPassword", $reponse->toArray());
    }
}
