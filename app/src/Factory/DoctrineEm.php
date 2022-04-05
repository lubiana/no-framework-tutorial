<?php declare(strict_types=1);

namespace Lubian\NoFramework\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\Setup;
use Lubian\NoFramework\Settings;

final class DoctrineEm
{
    public function __construct(private Settings $settings)
    {
    }

    public function create(): EntityManagerInterface
    {
        $config = Setup::createConfiguration($this->settings->doctrine['devMode']);

        $config->setMetadataDriverImpl(
            new AttributeDriver(
                $this->settings->doctrine['metadataDirs']
            )
        );

        return EntityManager::create(
            $this->settings->connection,
            $config,
        );
    }
}
