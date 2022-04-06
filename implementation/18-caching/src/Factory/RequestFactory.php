<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RequestFactory extends ServerRequestFactoryInterface
{
    public function fromGlobals(): ServerRequestInterface;
}
