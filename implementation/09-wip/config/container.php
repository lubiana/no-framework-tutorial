<?php declare(strict_types=1);

$response = new \Laminas\Diactoros\Response();
$clock = new \Lubian\NoFramework\Service\Time\SystemClock();

return [
    \Lubian\NoFramework\Action\Hello::class => fn () => new \Lubian\NoFramework\Action\Hello($response, $clock),
    \Lubian\NoFramework\Action\Other::class => fn () => new \Lubian\NoFramework\Action\Other($response),
];
