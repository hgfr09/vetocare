<?php

namespace App\Tests\Functional;

use App\Factory\AnimalFactory;
use App\Factory\ConsultationFactory;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

final class  ConsultationTest extends AbstractApiTestCase
{
    // Create
    public function testVetoCanCreateConsultation(): void
    {
        $animal = AnimalFactory::createOne();

        $this->browserAsVeto()->post('/api/consultations', [
            'json' => [
                'reason' => 'test1',
                'diagnosis' => 'diagnosis1',
                'animal' =>  '/api/animals/' . $animal->getId()
            ]
        ])
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonMatches('veterinarian != null', true)
            ->assertJsonMatches('reason', 'test1');
    }

    // Read
    public function testGetCollectionOfConsultations(): void
    {
        ConsultationFactory::createOne(['reason' => 'test1']);

        $this->browserAsVeto()->get('/api/consultations')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('totalItems', 1)
            ->assertJsonMatches('member[0].reason', 'test1');
    }

    public function testCanFilterConsultationsByAnimalName(): void
    {
        ConsultationFactory::createOne(['reason' => 'test1']);
        ConsultationFactory::createOne([
            'animal' => AnimalFactory::createOne(['name' => 'test_animal'])
        ]);

        $this->browserAsVeto()->get('/api/consultations?animal.name=test_animal')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('totalItems', 1);
    }

    public function testGetAConsultation(): void
    {
        $consultation = ConsultationFactory::createOne(['reason' => 'test1']);

        $this->browserAsVeto()->get('/api/consultations/' . $consultation->getId())
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('reason', 'test1');
    }

    public function testGetConsultationsOfAVeto(): void
    {
        $consultation = ConsultationFactory::createOne(['reason' => 'test1']);
        $user = $consultation->getVeterinarian();
        $this->browserAsVeto($user)->get('/api/users/' . $user->getId() . '/consultations')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('totalItems', 1)
            ->assertJsonMatches('member[0].reason', 'test1');
    }

    public function testReturns404IfConsultationNotFound(): void
    {
        $this->browserAsVeto()->get('/api/consultations/999999')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    // Update
    public function testCanUpdateConsultation(): void
    {
        $consultation = ConsultationFactory::createOne(['reason' => 'test1']);
        $this->patchAsVeto('/api/consultations/' . $consultation->getId(), ['reason' => 'new reason'])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('reason', 'new reason');
    }

    // Delete
    public function testAdminCanDeleteConsultation(): void
    {
        $consultation = ConsultationFactory::createOne();
        $this->browserAsAdmin()->delete('/api/consultations/' . $consultation->getId())
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function testVetoCannotDeleteConsultation(): void
    {
        $consultation = ConsultationFactory::createOne();
        $this->browserAsVeto()->delete('/api/consultations/' . $consultation->getId())
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    // Business Rules
    public function testCannotCreateConsultationWhenDateInFuture(): void
    {
        $animal = AnimalFactory::createOne(['dateOfBirth' => new DateTimeImmutable('now')]);

        $this->browserAsVeto()->post('/api/consultations', [
            'json' => [
                'reason' => 'test 1',
                'diagnosis' => 'diagnosis 1',
                'animal' =>  '/api/animals/' . $animal->getId(),
                'date' => date('Y-m-d', strtotime('+1 day'))
            ]
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    public function testCannotCreateConsultationWhenDateIsBeforeAnimalBirthDate(): void
    {
        $animal = AnimalFactory::createOne(['dateOfBirth' => new \DateTimeImmutable('tomorrow')]);

        $this->browserAsVeto()->post('/api/consultations', [
            'json' => [
                'reason' => 'test1',
                'diagnosis' => 'diagnosis1',
                'animal' =>  '/api/animals/' . $animal->getId(),
            ]
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[DataProvider('invalidDataProvider')]
    public function testCannotCreateInvalidConsultation(string $field, ?string $value): void
    {
        $animal = AnimalFactory::createOne();
        $attributes = array_merge([
            'reason' => 'test 1',
            'diagnosis' => 'diagnosis 1',
            'animal' =>  '/api/animals/' . $animal->getId()
        ], [$field => $value]);

        if ($value === '' || $value === null) {
            unset($attributes[$field]);
        }

        $this->browserAsVeto()->post('/api/consultations', ['json' => $attributes])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    public static function invalidDataProvider(): iterable
    {
        yield 'The reason of the consultation is compulsory' => ['reason', ''];
        yield 'The diagnosis of the consultation is compulsory' => ['diagnosis', ''];
        yield 'The animal to examine is compulsory' => ['animal', null];
    }
}
