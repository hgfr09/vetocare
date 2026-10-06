<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\User;
use App\Interface\UserAwareInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsDecorator('api_platform.doctrine.orm.state.persist_processor')]
class UserAwareProcessor implements ProcessorInterface
{
    public function __construct(
        #[AutowireDecorated] private ProcessorInterface $innerProcessor,
        private Security $security,
        private ValidatorInterface $validator
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof UserAwareInterface && null === $data->getVeterinarian()) {
            $user = $this->security->getUser();
            if ($user  instanceof User) {
                $data->setVeterinarian($user);
            } else {
                $violations = $this->validator->validate($data, groups: ['internal']);
                if ($violations->count() > 0) {
                    throw new ValidationException($violations);
                }
            }
        }

        return $this->innerProcessor->process($data, $operation, $uriVariables, $context);
    }
}
