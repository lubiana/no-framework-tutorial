<?php

declare(strict_types=1);

$builder = new \DI\ContainerBuilder();
$builder->addDefinitions([
    \Psr\Http\Message\ResponseInterface::class => \DI\create(\Laminas\Diactoros\Response::class),
    \Psr\Http\Message\ServerRequestInterface::class => fn () => \Laminas\Diactoros\ServerRequestFactory::fromGlobals(),
]);

return $builder->build();
