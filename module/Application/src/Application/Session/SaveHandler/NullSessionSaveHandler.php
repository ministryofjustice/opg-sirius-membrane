<?php

declare(strict_types=1);

namespace Application\Session\SaveHandler;

use Laminas\Session\SaveHandler\SaveHandlerInterface;
use SessionHandler;

/**
 * Null Session Save Handler
 *
 * Wraps default SessionHandler object such that it can be used in with
 * Laminas' Session Manager. Used to all encryption of sessions while still
 * using PHP's built-in session storage mechanism.
 */
class NullSessionSaveHandler extends SessionHandler implements SaveHandlerInterface
{
}
