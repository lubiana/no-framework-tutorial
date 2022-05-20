<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Time;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

}