<?php

declare(strict_types=1);

namespace Application\Authentication\Storage;

use Application\Model\Entity\UserAccount;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use JwtLaminasAuth\Authentication\Storage\JwtStorage;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DoctrineUserAccountFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return DoctrineUserAccount
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): DoctrineUserAccount
    {
        $repository = $container->get(EntityManager::class)->getRepository(UserAccount::class);

        return new DoctrineUserAccount($container->get(JwtStorage::class), $repository);
    }
}
