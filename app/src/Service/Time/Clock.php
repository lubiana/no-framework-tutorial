<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Time;

interface Clock
{
    public function now(): \DateTimeImmutable;
}