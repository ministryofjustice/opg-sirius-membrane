<?php
declare(strict_types=1);
namespace ApplicationTest\Config;

use Application\Config\SessionSaveHandlerConfigMapper;
use AwsModule\Session\SaveHandler\DynamoDb;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class SessionSaveHandlerConfigMapperTest extends TestCase
{
    private $currentEnvValue;

    public function setup(): void
    {
        $this->currentEnvValue = getenv('OPG_CORE_MEMBRANE_SESSION_SAVE_HANDLER');
    }

    public function teardown(): void
    {
        putenv('OPG_CORE_MEMBRANE_SESSION_SAVE_HANDLER=' . $this->currentEnvValue);
    }

    public function test_environment_variable_undefined_returns_null()
    {
        putenv("OPG_CORE_MEMBRANE_SESSION_SAVE_HANDLER=");
        $this->assertNull(SessionSaveHandlerConfigMapper::getConfig());
    }

    public function test_environment_variable_set_to_dynamo_returns_correct_class()
    {
        putenv("OPG_CORE_MEMBRANE_SESSION_SAVE_HANDLER=DynamoDb");
        $this->assertEquals(DynamoDb::class, SessionSaveHandlerConfigMapper::getConfig());
    }

    public function test_environment_variable_set_to_legacy_dynamo_returns_correct_class()
    {
        putenv("OPG_CORE_MEMBRANE_SESSION_SAVE_HANDLER=Aws\Session\SaveHandler\DynamoDb");
        $this->assertEquals(DynamoDb::class, SessionSaveHandlerConfigMapper::getConfig());
    }

    public function test_environment_variable_set_to_invalid_value_causes_exception()
    {
        putenv("OPG_CORE_MEMBRANE_SESSION_SAVE_HANDLER=FOO");
        $this->expectException(InvalidArgumentException::class);
        SessionSaveHandlerConfigMapper::getConfig();
    }
}
