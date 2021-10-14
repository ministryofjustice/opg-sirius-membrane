<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Log\Filter\Priority;
use Laminas\Log\Writer\WriterInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Logger;

class LoggerServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return Logger
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Logger
    {
        $config = $container->get('Config')['sirius_logger'];

        $logger = new Logger();

        foreach ($config['writers'] as $writer) {
            if (!$writer['enabled']) {
                continue;
            }

            /** @var WriterInterface $writerAdapter */
            $writerAdapter = new $writer['adapter']($writer['adapterOptions']['output']);

            $logger->addWriter($writerAdapter);

            if (!empty($writer['formatter'])) {
                $writerFormatter = new $writer['formatter']($writer['formatterOptions']);
                $writerAdapter->setFormatter($writerFormatter);
            }

            $writerAdapter->addFilter(
                new Priority(
                    $writer['filter']
                )
            );
        }

        if (
            !isset($config['sirius_logger']['symfonyErrorHandler'])
            || true !== $config['sirius_logger']['symfonyErrorHandler']
        ) {
            Logger::registerErrorHandler($logger);
        }

        return $logger;
    }
}
