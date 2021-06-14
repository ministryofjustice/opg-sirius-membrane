<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Application\Model\Entity\UserAccount;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class LoadTestUsers implements FixtureInterface
{
    const USER_CSV_PATH = './module/Application/src/Application/Fixtures/Data/users.csv';
    const EMAIL_INDEX = 2;
    const ROLES_INDEX = 3;
    const PASSWORD_INDEX = 4;

    public function load(ObjectManager $manager): void
    {
        $usersCsv = fopen(self::USER_CSV_PATH, 'r');
        if ($usersCsv === false) {
            throw new RuntimeException('Could not open ' . getcwd() . '/' . self::USER_CSV_PATH);
        }

        // Skip heading row
        fgetcsv($usersCsv);

        while (($data = fgetcsv($usersCsv)) !== false) {
            $this->addUser(
                $manager,
                $this->isAdmin($data[self::ROLES_INDEX]),
                trim($data[self::EMAIL_INDEX]),
                trim($data[self::PASSWORD_INDEX])
            );
        }

        $manager->flush();
    }

    public function isAdmin(string $roles): bool
    {
        // Split the roles
        $roles = array_map('trim', explode(',', $roles));

        return in_array('System Admin', $roles);
    }

    public function addUser(ObjectManager $objectManager, bool $isAdmin, string $email, string $passwordHash): void
    {
        $userAccount = new UserAccount();
        $userAccount->setAdmin($isAdmin);
        $userAccount->setEmail($email);
        $userAccount->setPassword($passwordHash);
        $userAccount->setStatus(UserAccount::STATUS_ACTIVE);

        $objectManager->persist($userAccount);
    }
}
