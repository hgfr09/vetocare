<?php

namespace App\Factory;

use App\Entity\Animal;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Animal>
 */
final class AnimalFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Animal::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'dateOfBirth' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-10 years')),
            'name' => self::faker()->unique()->firstName(),
            'ownerName' => self::faker()->name(),
            'species' => self::faker()->sentence(2),
            'breed'=> self::faker()->sentence(3)
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Animal $animal): void {})
        ;
    }
}
