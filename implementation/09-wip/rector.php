<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__ . '/src', __DIR__ . '/config', __DIR__ . '/ecs.php', __DIR__ . '/rector.php']);

    $rectorConfig->importNames();

    $rectorConfig->sets([LevelSetList::UP_TO_PHP_81]);
};
