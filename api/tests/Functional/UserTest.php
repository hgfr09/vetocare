<?php

namespace App\Tests\Functional;

use App\Factory\ConsultationFactory;
use App\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

class UserTest extends AbstractApiTestCase
{
    // Create
    public function testAdminCanCreateUser(): void
    {
        $this->browserAsAdmin()->post('/api/users', [
            'json' => [
                'email' => 'test@test.com',
                'plainPassword' => '123456'
            ]
        ])
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonMatches('email', 'test@test.com');
    }

    public function testEmailAlreadyExists(): void
    {
        UserFactory::createOne(['email' => 'test@test.com']);

        $this->browserAsAdmin()->post('/api/users', [
            'json' => [
                'email' => 'test@test.com',
                'plainPassword' => '123456'
            ]
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testVetoCannotCreateUser(): void
    {
        $this->browserAsVeto()->post('/api/users', [
            'json' => [
                'email' => 'test@test.com',
                'plainPassword' => '123456'
            ]
        ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[DataProvider('invalidUserProvider')]
    public function testCannotCreateInvalidUser(string $field, ?string $value): void
    {
        $userData = array_merge([
            'email' => 'test@test.com',
            'plainPassword' => '123456'
        ], [$field => $value]);

        if ($value === '' || $value === null) {
            unset($userData[$field]);
        }

        $this->browserAsAdmin()
            ->post('/api/users', ['json' => $userData])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function invalidUserProvider(): iterable
    {
        yield 'The user email is compulsory' => ['email', ''];
        yield 'The user email is invalid' => ['email', 'email@'];
        yield 'The user password is compulsory' => ['plainPassword', null];
        yield 'The user password is too short' => ['plainPassword', '1234'];
    }

    // Read
    public function testVetoCannotAccessOthersData(): void
    {
        $user = UserFactory::createVeterinarian();
        $this->browserAsVeto()->get('/api/users/' . $user->getId())
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testVetoCanAccessOwnData(): void
    {
        $user = UserFactory::createVeterinarian();
        $this->browserAsVeto($user)->get('/api/users/' . $user->getId())
            ->assertStatus(Response::HTTP_OK);
    }

    public function testVetoCannotGetCollectionOfUsers(): void
    {
        $this->browserAsVeto()->get('/api/users')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanGetCollectionOfUsers(): void
    {
        $users = UserFactory::createMany(2);
        $this->browserAsAdmin()->get('/api/users')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('totalItems', 3);
    }

    // Update
    public function testUserCanUpdateOwnData(): void
    {
        $user = UserFactory::createVeterinarian(['email' => 'veto@test.com']);
        $this->patchAsVeto('/api/users/' . $user->getId(), ['email' => 'veto_changed@test.com'], $user)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('email', 'veto_changed@test.com');
    }

    public function testUserCannotUpdateOthersData(): void
    {
        $user = UserFactory::createVeterinarian(['email' => 'veto@test.com']);
        $this->patchAsVeto('/api/users/' . $user->getId(), ['email' => 'veto_changed@test.com'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanUpdateData(): void
    {
        $user = UserFactory::createVeterinarian(['email' => 'veto@test.com']);
        $admin = UserFactory::createAdmin();
        $this->patchAsAdmin('/api/users/' . $user->getId(), ['email' => 'veto_changed@test.com'], $admin)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonMatches('email', 'veto_changed@test.com');
    }

    // Delete
    public function testAdminCanDeleteUser(): void
    {
        $user = UserFactory::createVeterinarian(['email' => 'veto@test.com']);
        $this->browserAsAdmin()->delete('/api/users/' . $user->getId())
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function testVetoCannotDeleteUser(): void
    {
        $user = UserFactory::createVeterinarian();
        $this->browserAsVeto()->delete('/api/users/' . $user->getId())
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testCannotDeleteUserHavingConsultations(): void
    {
        $veto = UserFactory::createVeterinarian();
        ConsultationFactory::createOne(['veterinarian' => $veto]);
        $this->browserAsAdmin()->delete('/api/users/' . $veto->getId())
            ->assertStatus(Response::HTTP_CONFLICT);
    }
}
