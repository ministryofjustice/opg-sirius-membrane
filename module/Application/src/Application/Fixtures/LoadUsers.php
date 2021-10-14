<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Application\Model\Entity\UserAccount;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadUsers implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $userAccount = new UserAccount();
        $userAccount->setAdmin(true);
        $userAccount->setEmail('admin@example.com');
        $userAccount->setPassword(getenv('OPG_CORE_MEMBRANE_USER_ONE_PASSWORD') ? getenv('OPG_CORE_MEMBRANE_USER_ONE_PASSWORD') : '23eBN6301COX5Aq55S1T77GP');
        $userAccount->setStatus(UserAccount::STATUS_ACTIVE);

        $manager->persist($userAccount);
        $manager->flush();
    }
}
