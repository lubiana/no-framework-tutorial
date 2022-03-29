<?php declare(strict_types=1);

namespace Lubian\NoFramework\Service\Time;

use DateTimeImmutable;

interface Now
{
    public function __invoke(): DateTimeImmutable;
}
