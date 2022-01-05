<?php

declare(strict_types=1);

namespace Application\Session\SaveHandler;

use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Filter\Encrypt;
use Laminas\Filter\Decrypt;

class EncryptedSessionSaveHandler implements SaveHandlerInterface
{
    public function __construct(
        protected \SessionHandlerInterface $sessionSaveHandler,
        protected Encrypt $encryptFilter,
        protected Decrypt $decryptFilter
    ) {
    }

    public function open(string $savePath, string $name): bool
    {
        return $this->sessionSaveHandler->open($savePath, $name);
    }

    public function close(): bool
    {
        return $this->sessionSaveHandler->close();
    }

    public function read(string $id): string
    {
        // Return the data from the cache
        $data = $this->sessionSaveHandler->read($id);

        // If there's no data, just return it (null)
        if (empty($data)) {
            return $data;
        }

        // Decrypt and return the data
        return $this->decryptFilter->filter($data);
    }

    public function write(string $id, string $data): bool
    {
        if (!empty($data)) {
            $data = $this->encryptFilter->filter($data);
        }

        // Pass the encrypted session data to the decorated save handler.
        return $this->sessionSaveHandler->write($id, $data);
    }

    public function destroy(string $id): bool
    {
        return $this->sessionSaveHandler->destroy($id);
    }

    public function gc(int $maxlifetime): bool
    {
        return $this->sessionSaveHandler->gc($maxlifetime);
    }
}
