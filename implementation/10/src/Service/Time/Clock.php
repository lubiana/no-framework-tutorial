<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Time;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
