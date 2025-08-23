<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip;

use App\Context\Slip\Domain\SlipRepository;
use App\Tests\Shared\Infrastructure\PhpUnit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

abstract class SlipModuleUnitTestCase extends UnitTestCase
{
    private SlipRepository|MockObject|null $repository = null;

    protected function repository(): SlipRepository|MockObject
    {
        return $this->repository ??= $this->createMock(SlipRepository::class);
    }
}
