<?php

namespace App\Shared\Application;

interface CommandBus
{
    public function dispatch(Command $command): void;
}