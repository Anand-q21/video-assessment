<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * @param User $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // Only hash password if it's not already hashed 
        if ($data->getPassword()) {
            $data->setPassword(
                $this->passwordHasher->hashPassword(
                    $data,
                    $data->getPassword()
                )
            );
        }

        // Let the default Doctrine processor save the user
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}