<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserAccount
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_NOT_ACTIVATED = 'not_activated';
    public const STATUS_LOCKED = 'locked';

    public const VALID_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_NOT_ACTIVATED,
        self::STATUS_LOCKED,
    ];

    public const PASSWORD_ALGO = PASSWORD_DEFAULT;
    public const PASSWORD_OPTIONS = [];

    /**
     * @ORM\Column(type = "integer", options = {"unsigned": true})
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     * @ORM\Id
     * @var int $id
     */
    protected $id;

    /**
     * @ORM\Column(type = "boolean")
     * @var boolean
     */
    protected $isAdmin;

    /**
     * @ORM\Column(type = "string", unique = true, nullable = false)
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(type = "string", nullable = true)
     * @var string
     */
    protected $password;

    /**
     * @ORM\Column(type = "datetime", nullable = true)
     * @var \DateTime
     */
    protected $created;

    /**
     * @ORM\Column(type = "datetime", nullable = true)
     * @var \DateTime
     */
    protected $updated;

    /**
     * @ORM\Column(type = "datetime", nullable = true)
     * @var \DateTime
     */
    protected $lastLoggedIn;

    /**
     * @ORM\Column(type = "string", nullable = false)
     * @var string $status
     */
    protected $status;

    /**
     * @ORM\Column(type = "string", nullable = true)
     * @var string|null
     */
    protected $oneTimePasswordSetToken;

    /**
     * @ORM\Column(type = "datetime", nullable = true)
     * @var \DateTime|null
     */
    protected $oneTimePasswordSetTokenGeneratedTime;

    /**
     * @ORM\Column(type = "integer", nullable = true, options = {"unsigned": true})
     * @var int $unsuccessfulLoginAttempts
     */
    protected $unsuccessfulLoginAttempts;

    public function __debugInfo()
    {
        $dump = get_object_vars($this);
        unset($dump['password']);
        unset($dump['oneTimePasswordSetToken']);

        return $dump;
    }

    /**
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @param bool $isAdmin
     * @return $this
     */
    public function setAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = password_hash($password, self::PASSWORD_ALGO, self::PASSWORD_OPTIONS);
        return $this;
    }

    public function clearPassword()
    {
        $this->password = null;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        // Enforce the status enum.
        if (in_array($status, self::VALID_STATUSES)) {
            $this->status = $status;
        } else {
            throw new \InvalidArgumentException(
                "Attempting to set UserAccount status to invalid status $status"
            );
        }

        return $this;
    }

    /**
     * Verify a password hash against a UserAccount
     *
     * @param UserAccount $userAccount
     * @param mixed $passwordGiven
     *
     * @return boolean
     */
    public static function verifyPasswordAndStatus(UserAccount $userAccount, $passwordGiven)
    {
        $result = password_verify($passwordGiven, $userAccount->getPassword());

        if ($result) {
            $userAccount->resetUnsuccessfulLoginAttempts();
            if (password_needs_rehash($userAccount->getPassword(), self::PASSWORD_ALGO, self::PASSWORD_OPTIONS)) {
                $userAccount->setPassword($passwordGiven);
            }
        }

        return $result;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->created = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue()
    {
        $this->updated = new \DateTime();
    }

    public function setLastLoggedInValue(): void
    {
        $this->lastLoggedIn = new \DateTime();
    }

    /**
     * @return $this
     */
    public function setOneTimePasswordSetToken()
    {
        $binaryToken = random_bytes(16);
        $base64URLSafeToken = trim(strtr(base64_encode($binaryToken), '+/', '-_'), '=');

        $this->oneTimePasswordSetToken = $base64URLSafeToken;
        $this->oneTimePasswordSetTokenGeneratedTime = new \DateTime('now', new \DateTimeZone('UTC'));

        return $this;
    }

    /**
     * @return string
     */
    public function getOneTimePasswordSetToken()
    {
        return $this->oneTimePasswordSetToken;
    }

    public function clearOneTimePasswordSetToken()
    {
        $this->oneTimePasswordSetToken = null;
        $this->oneTimePasswordSetTokenGeneratedTime = null;
    }

    /**
     * @param string $token
     * @param \DateInterval $tokenLifespan
     * @return bool
     */
    public function validateOneTimePasswordSetToken($token, \DateInterval $tokenLifespan): bool
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        if (empty($this->oneTimePasswordSetTokenGeneratedTime) || empty($this->oneTimePasswordSetToken)) {
            return false;
        }

        // Necessary because DateTime->add() method modifies referenced object.
        $oneTimePasswordSetTokenGeneratedTimeClone = clone $this->oneTimePasswordSetTokenGeneratedTime;
        $expiryTime = $oneTimePasswordSetTokenGeneratedTimeClone->add($tokenLifespan);

        return $token === $this->oneTimePasswordSetToken
            && $now >= $this->oneTimePasswordSetTokenGeneratedTime
            && $now <= $expiryTime;
    }

    /**
     * @return $this
     */
    public function incrementUnsuccessfulLoginAttempts()
    {
        $this->unsuccessfulLoginAttempts++;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetUnsuccessfulLoginAttempts()
    {
        $this->unsuccessfulLoginAttempts = 0;

        return $this;
    }

    /**
     * @param int $unsuccessfulLoginLimit
     *
     * @return boolean
     */
    public function validateUserHasExceededUnsuccessfulLoginAttempts($unsuccessfulLoginLimit = 3)
    {
        return $this->unsuccessfulLoginAttempts >= $unsuccessfulLoginLimit;
    }

    public function isLocked()
    {
        return $this->status === self::STATUS_LOCKED || $this->status === self::STATUS_SUSPENDED || $this->status === self::STATUS_NOT_ACTIVATED;
    }
}
