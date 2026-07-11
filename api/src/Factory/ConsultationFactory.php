<?php

namespace App\Factory;

use App\Entity\Consultation;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Consultation>
 */
final class ConsultationFactory extends PersistentObjectFactory
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
        return Consultation::class;
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
            'animal' => AnimalFactory::randomOrCreate(),
            'diagnosis' => self::faker()->text(200),
            'reason' => self::faker()->text(100),
            'prescribedTreatment'=>self::faker()->text(200),
            'date'=>\DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-4 years'))
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(
                Instantiator::withConstructor()->alwaysForce('date')
            );
        ;
    }
}
