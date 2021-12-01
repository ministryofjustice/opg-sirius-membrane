<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Aws\DynamoDb\SessionHandler;
use Aws\Sdk;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DynamoDbSaveHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<mixed>|null $options
     * @return SessionHandler
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SessionHandler
    {
        $config = $container->get('Config');

        $dynamoDb = $container->get(Sdk::class)->createDynamoDb();

        return SessionHandler::fromClient($dynamoDb, $config['session']['save_handler']['dynamodb']);
    }
}
