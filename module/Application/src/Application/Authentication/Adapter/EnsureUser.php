<?php

namespace Application\Authentication\Adapter;

use Application\Model\Entity\UserAccount;
use DoctrineModule\Authentication\Adapter\ObjectRepository;
use Laminas\Authentication\Result;

class EnsureUser implements ChainingAdapterInterface
{
    private $wrapped;

    public function __construct(ObjectRepository $wrappedAdapter)
    {
        $this->wrapped = $wrappedAdapter;
    }

    public function setIdentity($identity)
    {
        $this->wrapped->setIdentity($identity);
    }

    public function setCredential($credential)
    {
        $this->wrapped->setCredential($credential);
    }

    public function authenticate()
    {
        $result = $this->wrapped->authenticate();

        if (!$result->isValid()) {
            $identity = $result->getIdentity();
            if (!($identity instanceof UserAccount)) {
                $options = $this->wrapped->getOptions();
                $identity = $options->getObjectRepository()->findOneBy([$options->getIdentityProperty() => $identity]);

                $result = new Result($result->getCode(), $identity, $result->getMessages());
            }
        }

        return $result;
    }
}
