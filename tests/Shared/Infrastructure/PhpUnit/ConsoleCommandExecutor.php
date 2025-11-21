<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\PhpUnit;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

trait ConsoleCommandExecutor
{
    protected function runCommand(string $commandWithArgs): int
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        // The first element is the command name, the rest are arguments
        $commandParts = explode(' ', $commandWithArgs);
        $commandName = $commandParts[0];
        
        $command = $application->find($commandName);
        $commandTester = new CommandTester($command);
        
        // Prepare arguments and options for the execute method
        $input = [];
        foreach ($commandParts as $index => $part) {
            if ($index === 0) {
                continue; // Skip command name
            }
            // Simple parsing: assumes arguments are separated by spaces
            // and options start with --
            if (str_starts_with($part, '--')) {
                $input[$part] = true; // Assumes options are flags
            } else {
                // This part might need to be more robust if you have arguments with spaces
                $input[] = $part;
            }
        }

        return $commandTester->execute($input);
    }
}
