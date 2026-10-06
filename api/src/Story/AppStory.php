<?php

namespace App\Story;

use App\Factory\AnimalFactory;
use App\Factory\ConsultationFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function build(): void
    {
        // Create users
        UserFactory::createMany(2);

        // Create admin
        UserFactory::createAdmin(['email' => 'admin@test.com']);

        // Create Animal
        AnimalFactory::createMany(10, function () {
            return [
                'veterinarian' => UserFactory::random()
            ];
        });

        // Create Consultation
        ConsultationFactory::createMany(50, function () {
            return [
                'veterinarian' => UserFactory::random()
            ];
        });
    }
}
