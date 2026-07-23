<?php

namespace App\Tests;

use App\Factory\AnimalFactory;
use App\Factory\UserFactory;

final class  ConsultationTest extends AbstractApiTestCase
{
    public function testCreateSuccessfullConsultation(): void
    {
        $animal = AnimalFactory::createOne();
        $user = UserFactory::createOne();

        static::createClient()->request('POST', '/api/consultations', [
            'headers' => self::$HEADERS_WRITE,
            'json' => [
                'animal' => '/api/animals/' . $animal->getId(),
                'reason' => 'Control',
                'diagnosis' => 'RAS',
                'veterinarian' => '/api/users/' . $user->getId()
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCannotCreateConsultationWhenAnimalBirthDateIsInFuture(): void
    {
        $animal = AnimalFactory::createOne([
            'dateOfBirth' => new \DateTimeImmutable()
        ]);

        $user = UserFactory::createOne();

        static::createClient()->request('POST', '/api/consultations', [
            'headers' => self::$HEADERS_WRITE,
            'json' => [
                'animal' => '/api/animals/' . $animal->getId(),
                'date' => new \DateTimeImmutable('yesterday')->format('c'),
                'reason' => 'Control',
                'diagnosis' => 'RAS',
                'veterinarian' => '/api/users/' . $user->getId()
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            "@type" => "ConstraintViolation",
            "violations" => [
                [
                    "propertyPath" => "date",
                    "message" => "La date de consultation ne peut être inférieure à la date de naissance du patient."
                ]
            ]
        ]);
    }
}
