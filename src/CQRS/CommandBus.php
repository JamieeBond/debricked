<?php

namespace App\CQRS;

interface CommandBus
{
    /**
     * @param Command $command
     * @return void
     */
    public function dispatch(Command $command): void;
}