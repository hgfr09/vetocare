<?php

namespace App\Tests;

use App\Factory\UserFactory;

final class AnimalTest extends AbstractApiTestCase
{
    // Creation
    public function testCreateAnimalSuccess(): void
    {
        $user = UserFactory::createVeterinarian();
        $client = $this->createAuthenticatedClient($user);

        $client->request('POST', '/api/animals', [
            'headers' => self::$HEADERS_WRITE,
            'json' => [
                'name' => 'Rex',
                'species' => 'Canidé',
                'dateOfBirth' => '2024-01-01',
                'ownerName' => 'Abram'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Rex',
            'species' => 'Canidé',
            'dateOfBirth' => '2024-01-01T00:00:00+00:00',
            'ownerName' => 'Abram',
            'veterinarian' => [
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ]
        ]);
    }

    //  Validation
    public function testCannotCreateAnimalWithInvalidOwnerName(): void
    {
        $client = $this->createAuthenticatedClient(UserFactory::createVeterinarian());

        $client->request('POST', '/api/animals', [
            'headers' => self::$HEADERS_WRITE,

            'json' => [
                'name' => 'Rex',
                'species' => 'Canidé',
                'dateOfBirth' => '2024-01-01',
                'ownerName' => 'Ab' // Trop court! (min 3 attendu)
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);

        $this->assertJsonContains([
            "@type" => "ConstraintViolation",
            "violations" => [
                [
                    "propertyPath" => "ownerName"
                ]
            ]
        ]);
    }

    // Business Rules
    public function testCannotCreateAnimalWithDateInFuture(): void
    {
        $client = $this->createAuthenticatedClient(UserFactory::createVeterinarian());
        $client->request('POST', '/api/animals', [
            'headers' => self::$HEADERS_WRITE,
            'json' => [
                'name' => 'Rex',
                'species' => 'Canidé',
                'dateOfBirth' => new \DateTimeImmutable('tomorrow')->format('c'),
                'ownerName' => 'John'
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            "@type" => "ConstraintViolation",
            "violations" => [
                [
                    "propertyPath" => "dateOfBirth"
                ]
            ]
        ]);
    }
}
