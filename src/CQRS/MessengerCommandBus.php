<?php

namespace App\CQRS;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * MessengerCommandBus abstraction of symfony messenger.
 * Layer between application code and symfony.
 */
final class MessengerCommandBus implements CommandBus
{
    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $commandBus;

    /**
     * @param MessageBusInterface $commandBus
     */
    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param Command $command
     * @return void
     */
    public function dispatch(Command $command): void
    {
        $this->commandBus->dispatch($command);
    }
}