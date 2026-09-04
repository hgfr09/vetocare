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
        $response = static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => "Daniel"
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertSame("user@test.com", $response->toArray()['email']);
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

        $this->createAuthenticatedClient($user)->request('PATCH', "/api/users/{$user->getId()}", [
            "headers" => self::$HEADERS_UPDATE,
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
    public function testAnonymousUserCannotAccessProtectedAPI(): void
    {
        static::createClient()->request('GET', '/api/animals');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUserCanAccessOwnProfile(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_VETO']]);
        $client = $this->createAuthenticatedClient($user);
        $response = $client->request('GET', "/api/users/{$user->getId()}", [
            'headers' => self::$HEADERS_READ
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame($user->getId(), $response->toArray()['id']);
    }

    public function testUserCanUpdateOwnProfile(): void
    {
        $user = UserFactory::createOne();
        $response = $this->createAuthenticatedClient($user)->request("PATCH", "/api/users/{$user->getId()}", [
            "headers" => self::$HEADERS_UPDATE,
            "json" => [
                "email" => "user-aupdated@test.com"
            ]
        ]);

        $data = $response->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertSame("user-aupdated@test.com", $data['email']);
        $this->assertSame($user->getId(), $data['id']);
    }

    public function testUserCannotAccessAnotherUsersProfile(): void
    {
        $user1 = UserFactory::createOne(['roles' => ['ROLE_VETO']]);
        $user2 = UserFactory::createOne(['roles' => ['ROLE_VETO']]);

        $client = $this->createAuthenticatedClient($user1);
        $client->request('GET', "/api/users/{$user2->getId()}", [
            'headers' => self::$HEADERS_READ
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserCannotUpdateAnotherUsersProfile(): void
    {
        $user1 = UserFactory::createOne(['roles' => ['ROLE_VETO']]);
        $user2 = UserFactory::createOne(['roles' => ['ROLE_VETO']]);

        $client = $this->createAuthenticatedClient($user1);
        $client->request('PATCH', "/api/users/{$user2->getId()}", [
            "headers" => self::$HEADERS_UPDATE,
            "json" => [
                "email"=>"updated-user@test.com"
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanAccessAnotherUsersProfile(): void
    {
        $admin = UserFactory::createAdmin();
        $user2 = UserFactory::createOne(['roles' => ['ROLE_VETO']]);

        $client = $this->createAuthenticatedClient($admin);
        $client->request('GET', "/api/users/{$user2->getId()}", [
            'headers' => self::$HEADERS_READ
        ]);

        $this->assertResponseIsSuccessful();
    }

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
        $response = static::createClient()->request('POST', '/api/users', [
            "headers" => self::$HEADERS_WRITE,
            "json" => [
                "email" => "user@test.com",
                "plainPassword" => "Daniel"
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArrayNotHasKey("plainPassword", $response->toArray());
    }

    public function testAdminCanAccessAllUsers(): void
    {
        $user = UserFactory::createOne();
        $admin = UserFactory::createAdmin();

        $client = $this->createAuthenticatedClient($admin);
        $response = $client->request('GET', '/api/users', [
            'headers' => self::$HEADERS_READ
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray()['member'];

        $this->assertCount(2, $data);
        $this->assertContains($user->getId(), array_column($data, 'id'));
        $this->assertContains($admin->getId(), array_column($data, 'id'));
    }

    public function testOnlyAdminCanDeleteUser(): void
    {
        $user = UserFactory::createOne(['roles' => ['ROLE_VETO']]);
        $admin = UserFactory::createAdmin();

        $this->createAuthenticatedClient($user)->request('DELETE', "/api/users/{$user->getId()}");
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client = $this->createAuthenticatedClient($admin);
        $client->request('DELETE', "/api/users/{$user->getId()}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', "/api/users/{$user->getId()}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
