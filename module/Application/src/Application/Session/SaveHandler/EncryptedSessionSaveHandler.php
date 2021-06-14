<?php

declare(strict_types=1);

namespace Application\Session\SaveHandler;

use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Filter\Encrypt;
use Laminas\Filter\Decrypt;

/**
 * Encrypted session save handler
 */
class EncryptedSessionSaveHandler implements SaveHandlerInterface
{
    /**
     * @var SaveHandlerInterface
     */
    protected $sessionSaveHandler;

    /**
     * @var Encrypt
     */
    protected $encryptFilter;

    /**
     * @var Decrypt
     */
    protected $decryptFilter;

    public function __construct(
        SaveHandlerInterface $sessionSaveHandler,
        Encrypt $encryptFilter,
        Decrypt $decryptFilter
    ) {
        $this->sessionSaveHandler = $sessionSaveHandler;
        $this->encryptFilter = $encryptFilter;
        $this->decryptFilter = $decryptFilter;
    }

    /**
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open($savePath, $name)
    {
        return $this->sessionSaveHandler->open($savePath, $name);
    }

    /**
     * @return bool
     */
    public function close()
    {
        return $this->sessionSaveHandler->close();
    }

    /**
     * @param string $id
     * @return string
     */
    public function read($id)
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

    /**
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data)
    {
        if (!empty($data)) {
            $data = $this->encryptFilter->filter($data);
        }

        // Pass the encrypted session data to the decorated save handler.
        return $this->sessionSaveHandler->write($id, $data);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function destroy($id)
    {
        return $this->sessionSaveHandler->destroy($id);
    }

    /**
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return $this->sessionSaveHandler->gc($maxlifetime);
    }
}
