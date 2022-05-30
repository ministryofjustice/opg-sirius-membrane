<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Service\MigrationVersionService as Version;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TestableSchemaManager extends AbstractSchemaManager
{
    public function listTableNames()
    {
        return [];
    }

    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        throw new RuntimeException('Not implemented');
    }
}

class MigrationVersionServiceTest extends TestCase
{
    private array $mockConfig = [
        'table_storage' => [
            'table_name' => 'tableName',
        ],
        'migrations_paths' => [
            'OPGCoreDoctrineMigrations\Fixture\DBMigrations' => '../../../../../MembraneDoctrineMigrations',
        ],
    ];

    /**
     * @var Connection
     */
    private $mockConnection;

    public function setUp(): void
    {
        $this->mockConnection = $this->createMock(Connection::class);
    }

    public function test_getCurrentVersion_returns_latest_version(): void
    {
        chdir(__DIR__);

        $this->mockConnection->expects(self::atLeastOnce())
            ->method('getDatabasePlatform')
            ->willReturn(new SqlitePlatform());

        $this->mockConnection->expects(self::atLeastOnce())
            ->method('createSchemaManager')
            ->willReturn(new TestableSchemaManager($this->mockConnection, new PostgreSQLPlatform()));

        $service = new Version($this->mockConnection, $this->mockConfig);
        $result = $service->getCurrentVersion();

        self::assertEquals('0', $result);
    }

    /**
     * @param array $config
     * @param Exception|null $expectedException
     *
     * @dataProvider incorrectConfigDataProvider
     */
    public function test_getCurrentVersion_throws_exceptions_when_missing_config(array $config, string $expectedWarningMessage = null)
    {
        if (!is_null($expectedWarningMessage)) {
            $this->expectWarning();
            $this->expectWarningMessage($expectedWarningMessage);
        }

        $service = new Version($this->mockConnection, $config);
        $service->getCurrentVersion();

        self::assertInstanceOf(Version::class, $service);
    }

    /**
     * @return array<mixed>
     */
    public function incorrectConfigDataProvider(): array
    {
        return [
            [
                'incorrectConfig' => [
                    'migrations_paths' => [
                        'OPGCoreDoctrineMigrations\Fixture\DBMigrations' => './Fixture/DBMigrations',
                    ],
                ],
                'expectedWarningMessage' => 'Undefined array key "table_storage"',
            ],
            [
                'incorrectConfig' => [
                    'table_storage' => [
                    ],
                    'migrations_paths' => [
                        'OPGCoreDoctrineMigrations\Fixture\DBMigrations' => './Fixture/DBMigrations',
                    ],
                ],
                'expectedWarningMessage' => 'Undefined array key "table_name"',
            ],
            [
                'incorrectConfig' => [
                    'table_storage' => [
                        'table_name' => 'tableName',
                    ],
                ],
                'expectedWarningMessage' => 'Undefined array key "migrations_paths"',
            ],
        ];
    }
}
