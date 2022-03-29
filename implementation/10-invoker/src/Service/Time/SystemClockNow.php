<?php

declare(strict_types=1);

namespace Lubian\NoFramework\Service\Time;

use DateTimeImmutable;

final class SystemClockNow implements Now
{
    public function get(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
