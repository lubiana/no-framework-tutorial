<?php

declare(strict_types=1);

$builder = new \DI\ContainerBuilder();
$builder->addDefinitions([
    \Psr\Http\Message\ResponseInterface::class => fn () => new \Laminas\Diactoros\Response(),
    \Psr\Http\Message\ServerRequestInterface::class => fn () => \Laminas\Diactoros\ServerRequestFactory::fromGlobals(),
    \Lubian\NoFramework\Service\Time\Now::class => fn () => new \Lubian\NoFramework\Service\Time\SystemClockNow(),
]);

return $builder->build();
