<?php

namespace App\Tests;

use App\Factory\AnimalFactory;
use App\Factory\ConsultationFactory;
use App\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;

final class  ConsultationTest extends AbstractApiTestCase
{
    // Creation
    public function testCreateConsultation(): void
    {
        $animal = AnimalFactory::createOne();
        $user = UserFactory::createOne(['roles' => ['ROLE_VETO']]);

        $client = $this->createAuthenticatedClient($user);

        $client->request('POST', '/api/consultations', [
            'headers' => self::$HEADERS_WRITE,
            'json' => [
                'animal' => '/api/animals/' . $animal->getId(),
                'reason' => 'Control',
                'diagnosis' => 'RAS'
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame($user->getId(), $client->getResponse()->toArray()['veterinarian']['id']);
    }

    // Read
    public function testSuccessfullyGetAllConsultationsOfAVeterinarian(): void
    {
        $veterinarian = UserFactory::createOne(['roles' => ['ROLE_VETO']]);
        AnimalFactory::createMany(2);
        ConsultationFactory::createMany(3, function () use ($veterinarian) {
            return ['veterinarian' => $veterinarian];
        });

        ConsultationFactory::createOne(['veterinarian' => UserFactory::createOne()]);

        $response = $this->createAuthenticatedClient($veterinarian)->request('GET', "api/users/{$veterinarian->getId()}/consultations", [
            "headers" => self::$HEADERS_READ
        ]);

        $this->assertResponseIsSuccessful();

        $jsonResponse = $response->toArray();

        $this->assertSame(3, $jsonResponse['totalItems']);

        foreach ($jsonResponse['member'] as $consultation) {
            $this->assertEquals($veterinarian->getId(), $consultation['veterinarian']['id']);
        }
    }

    // Business Rules
    public function testCannotCreateConsultationWhenDateIsBeforeAnimalBirthDate(): void
    {
        $animal = AnimalFactory::createOne([
            'dateOfBirth' => new \DateTimeImmutable()
        ]);

        $user = UserFactory::createOne(['roles' => ['ROLE_VETO']]);

        $client = $this->createAuthenticatedClient($user);

        $client->request('POST', '/api/consultations', [
            'headers' => self::$HEADERS_WRITE,
            'json' => [
                'animal' => '/api/animals/' . $animal->getId(),
                'date' => new \DateTimeImmutable('yesterday')->format('c'),
                'reason' => 'Control',
                'diagnosis' => 'RAS'
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
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
