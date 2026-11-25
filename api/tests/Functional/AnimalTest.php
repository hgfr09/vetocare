<?php

namespace App\Tests\Functional;

use App\Factory\AnimalFactory;
use App\Factory\ConsultationFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

final class AnimalTest extends AbstractApiTestCase
{
    // Create
    public function testVetoCanCreateAnimal(): void
    {
        $this->browserAsVeto()->post('/api/animals', [
            'json' => [
                'name' => 'Rex',
                'species' => 'Canidé',
                'dateOfBirth' => '2024-01-01',
                'ownerName' => 'Abram'
            ]
        ])
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonMatches('name', 'Rex')
            ->assertJsonMatches('ownerName', 'Abram');
    }

    // Read
    public function testCanGetAnimalItem(): void
    {
        $animal = AnimalFactory::createOne(['name' => 'Félix']);

        $this->browserAsVeto()->get('/api/animals/' . $animal->getId())
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('name', 'Félix');
    }

    public function testCanGetCollectionOfAnimals(): void
    {
        AnimalFactory::createOne(['name' => 'Félix']);
        AnimalFactory::createOne(['name' => 'Rex']);

        $this->browserAsVeto()->get('/api/animals')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('totalItems', 2)
            ->assertJsonMatches('member[0].name', 'Félix')
            ->assertJsonMatches('member[1].name', 'Rex');
    }

    public function testReturns404IfAnimalNotFound(): void
    {
        $this->browserAsVeto()->get('/api/animals/99999')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    // Patch
    public function testCanUpdateAnimal(): void
    {
        $animal = AnimalFactory::createOne(['name' => 'Félix']);

        $this->patchAsVeto('/api/animals/' . $animal->getId(), ['name' => 'Rex'])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('name', 'Rex');
    }

    public function testUpdateAnimalWillFailWithInvalidData(): void
    {
        $animal = AnimalFactory::createOne(['ownerName' => 'Félix']);

        $this->patchAsVeto('/api/animals/' . $animal->getId(), ['ownerName' => 'Re'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Delete
    public function testVetoCannotDeleteAnimal(): void
    {
        $animal = AnimalFactory::createOne(['ownerName' => 'Félix']);

        $this->browserAsVeto()->delete('/api/animals/' . $animal->getId())
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteAnimal(): void
    {
        $animal = AnimalFactory::createOne(['ownerName' => 'Félix']);

        $this->browserAsAdmin()->delete('/api/animals/' . $animal->getId())
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }

    // Business Rules
    public function testCannotDeleteAnimalHavingConsultation(): void
    {
        $animal = AnimalFactory::createOne(['ownerName' => 'Félix']);
        ConsultationFactory::createOne(['animal' => $animal]);

        $this->browserAsAdmin()->delete('/api/animals/' . $animal->getId())
            ->assertStatus(Response::HTTP_CONFLICT);
    }

    //  Validation
    #[DataProvider('invalidDataProvider')]
    public function testCannotCreateInvalidAnimal(string $property, mixed $value): void
    {
        $data = array_merge([
            'name' => 'Rex',
            'species' => 'Canidé',
            'dateOfBirth' => date('Y-m-d'),
            'ownerName' => 'Abram'
        ], [$property => $value]);

        if ($value === null || $value === '') {
            unset($data[$property]);
        }

        $this->browserAsVeto()->post('/api/animals', ['json' => $data])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonMatches('violations[0].propertyPath', $property);
    }

    public static function invalidDataProvider(): iterable
    {
        $dateInFuture = date('Y-m-d', strtotime('+1 day'));

        yield 'The name of the animal is compulsory' => ['name', ''];
        yield 'The species of the animal is compulsory' => ['species', ''];
        yield 'The animal date of birth is compulsory' => ['dateOfBirth', null];
        yield 'The animal date of birth cannot be in future' => ['dateOfBirth', $dateInFuture];
        yield 'The owner name of the animal is compulsory' => ['ownerName', ''];
        yield 'The owner name of the animal is too short' => ['ownerName', 'Ab'];
    }
}
