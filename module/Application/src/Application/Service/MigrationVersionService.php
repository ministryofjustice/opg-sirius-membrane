<?php

declare(strict_types=1);

namespace Application\Service;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use RuntimeException;

class MigrationVersionService
{
    /**
     * @see default keys under migrations_configuration, orm_default at
     *   https://github.com/doctrine/DoctrineORMModule/blob/3.1.x/config/module.config.php#L158
     */
    public const TABLE_CONFIG_SECTION_KEY = 'table_storage';
    public const MIGRATION_TABLE_KEY = 'table_name';
    public const MIGRATION_PATHS_SECTION_KEY = 'migrations_paths';

    public const ERR_MSG_MISSING_KEY = 'Expected key "%s" is missing in the doctrine\'s migration configuration!';

    private ?DependencyFactory $dependencyFactory = null;

    /**
     * @param array<mixed> $doctrineMigrationConfig
     *   in a usual setup comes from 'doctrine' => [ 'migrations_configuration' => ['orm_default' => []]
     */
    public function __construct(private Connection $conn, private array $doctrineMigrationConfig)
    {
    }

    public function getCurrentVersion(): string
    {
        return strval($this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current'));
    }

    private function getDependencyFactory(): DependencyFactory
    {
        if (is_null($this->dependencyFactory)) {
            $this->initMigrationConfiguration();
        }

        return $this->dependencyFactory;
    }

    private function initMigrationConfiguration(): void
    {
        $migrationTable = $this->getMigrationTable();
        $migrationPaths = $this->getMigrationPaths();

        $storageConfiguration = new TableMetadataStorageConfiguration();
        $storageConfiguration->setTableName($migrationTable);

        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration($storageConfiguration);

        foreach ($migrationPaths as $namespace => $path) {
            $configuration->addMigrationsDirectory($namespace, $path);
        }

        $this->dependencyFactory = DependencyFactory::fromConnection(new ExistingConfiguration($configuration), new ExistingConnection($this->conn));
    }

    /**
     * @return array<string, string>
     */
    private function getMigrationPaths(): array
    {
        $paths = $this->doctrineMigrationConfig[self::MIGRATION_PATHS_SECTION_KEY];
        if (!is_array($paths)) {
            throw new RuntimeException(sprintf('Expected %s to be an array, but it is not', self::MIGRATION_PATHS_SECTION_KEY));
        }

        return $paths;
    }

    private function getMigrationTable(): string
    {
        $table = $this->doctrineMigrationConfig[self::TABLE_CONFIG_SECTION_KEY][self::MIGRATION_TABLE_KEY];
        if (!is_string($table)) {
            throw new RuntimeException(sprintf('Expected %s to be a string, but it is not', self::MIGRATION_PATHS_SECTION_KEY));
        }

        return $table;
    }
}
