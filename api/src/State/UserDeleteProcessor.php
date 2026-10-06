<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class UserDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
    ) {}
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof User) {
            if (!$data->getAnimals()->isEmpty()) {
                throw new ConflictHttpException("Impossible de supprimer un utilisateur associé à des animaux.");
            }

            if (!$data->getConsultations()->isEmpty()) {
                throw new ConflictHttpException("Impossible de supprimer un utilisateur ayant des consultations.");
            }
        }

        return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
    }
}
