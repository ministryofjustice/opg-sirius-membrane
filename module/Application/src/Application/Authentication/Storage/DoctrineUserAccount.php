<?php

declare(strict_types=1);

namespace Application\Authentication\Storage;

use Application\Model\Entity\UserAccount;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Laminas\Authentication\Storage\StorageInterface;

class DoctrineUserAccount implements StorageInterface
{
    private StorageInterface $storage;

    /**
     * @var ObjectRepository<UserAccount> $userRepository
     */
    private $userRepository;

    private $userCache = [];

    /**
     * @param StorageInterface $storage
     * @param ObjectRepository<UserAccount> $userRepository
     */
    public function __construct(StorageInterface $storage, ObjectRepository $userRepository)
    {
        $this->storage = $storage;
        $this->userRepository = $userRepository;
    }

    public function isEmpty(): bool
    {
        return $this->storage->isEmpty();
    }

    /**
     * @return null|object
     */
    public function read()
    {
        $email = $this->storage->read();
        if ($email === null) {
            return null;
        }

        if (!isset($this->userCache[$email])) {
            $this->userCache[$email] = $this->userRepository->findOneBy(['email' => $email]);
        }

        return $this->userCache[$email];
    }

    /**
     * @param mixed $contents
     * @throws Exception
     */
    public function write($contents)
    {
        if (!$contents instanceof UserAccount) {
            throw new Exception(get_class($contents) . ' is not an instance of ' . UserAccount::class);
        }

        $this->storage->write($contents->getEmail());
        $this->userCache[$contents->getEmail()] = $contents;
    }

    public function clear()
    {
        $this->storage->clear();
        $this->userCache = [];
    }
}
