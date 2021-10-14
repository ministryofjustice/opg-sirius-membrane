<?php

declare(strict_types=1);

namespace Application\Service;

use Exception;
use JwtLaminasAuth\Authentication\Storage\Header;
use JwtLaminasAuth\Authentication\Storage\JwtStorage;
use JwtLaminasAuth\Service\JwtService;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Headers;
use Laminas\Http\Request;

class RequestService
{
    public const HEADER_USER_ID = 'X-USER-ID';
    public const HEADER_USER_ROLES = 'X-USER-ROLES';
    public const HEADER_SECURE_TOKEN = 'HTTP-SECURE-TOKEN';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var JwtService
     */
    private $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Ensure the all headers are present and that
     * no unrecognised headers are present
     *
     * @return bool @boolean
     * @throws Exception
     */
    public function areInvalidHeadersPresent()
    {
        if ($this->request === null) {
            throw new Exception('Request object not set');
        }

        if ($this->request->getHeader(self::HEADER_USER_ID)) {
            return true;
        }

        if ($this->request->getHeader(self::HEADER_USER_ROLES)) {
            return true;
        }

        return false;
    }

    /**
     * Return the secure token from the header.  Return null if
     * no token provided.
     *
     * @return string|null
     */
    public function getSecureToken()
    {
        $header = $this->request->getHeader(self::HEADER_SECURE_TOKEN);

        if ($header instanceof HeaderInterface) {
            return $header->getFieldValue();
        }

        return null;
    }

    /**
     * Determine if this is a request that needs to be
     * sent to devise.
     *
     * @return boolean
     */
    public function isDeviseRequest()
    {
        $path = $this->request->getUri()->getPath();

        $result = preg_match('/^\/auth\//', $path);

        return $result > 0;
    }

    /**
     * Determine if this is a login request.
     *
     * @return boolean
     */
    public function isLoginRequest()
    {
        $path = $this->request->getUri()->getPath();

        $loginResult = preg_match('/^\/auth\/login/', $path);
        $sessionResult = preg_match('/^\/auth\/session/', $path);

        return $loginResult > 0 || $sessionResult > 0;
    }

    /**
     * Populate headers with UserID and remove Secure Token
     *
     * @param string $userId
     */
    public function updateHeadersWithUserId(string $userId)
    {
        /** @var Headers $headers */
        $headers = $this->request->getHeaders();

        $secureTokenHeader = $this->request->getHeader(self::HEADER_SECURE_TOKEN);
        if ($secureTokenHeader instanceof HeaderInterface) {
            $headers->removeHeader($secureTokenHeader);
        }

        $headers->addHeaderLine(self::HEADER_USER_ID, $userId);
        $headers->addHeaderLine(
            Header::HEADER_NAME,
            'Bearer ' . $this->jwtService->createSignedToken(JwtStorage::SESSION_CLAIM_NAME, $userId, JwtStorage::DEFAULT_EXPIRATION_SECS)->toString()
        );
    }
}
