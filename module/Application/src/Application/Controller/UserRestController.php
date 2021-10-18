<?php

namespace Application\Controller;

use Application\Exception\UserAlreadyExistsException;
use Application\Model\Entity\UserAccount;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\SecurityLogger;
use Application\Service\UserCreationService;
use Application\Service\UserService;
use Application\Service\UserUpdateService;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Log\LoggerInterface;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

/**
 * @method Request getRequest()
 * @method Response getResponse()
 */
class UserRestController extends AbstractRestfulController
{
    use BypassMembraneHeader;

    protected AuthenticationServiceConstructor $authenticationServiceConstructor;
    protected UserService $userService;
    protected UserCreationService $userCreationService;
    protected UserUpdateService $userUpdateService;
    private LoggerInterface $logger;
    private SecurityLogger $securityLogger;

    public function __construct(
        AuthenticationServiceConstructor $authenticationServiceConstructor,
        UserService $userService,
        UserCreationService $userCreationService,
        UserUpdateService $userUpdateService,
        LoggerInterface $logger,
        SecurityLogger $securityLogger
    ) {
        $this->authenticationServiceConstructor = $authenticationServiceConstructor;
        $this->userService = $userService;
        $this->userCreationService = $userCreationService;
        $this->userUpdateService = $userUpdateService;
        $this->logger = $logger;
        $this->securityLogger = $securityLogger;
    }

    public function getList()
    {
        // Hand off URL parameters to service the retrieves list of users.
        $email = $this->params()->fromQuery('email', null);
        $params = [];
        if (!empty($email)) {
            $params['email'] = $email;
        }

        $userAccountArray = $this->userService->getUsers($params);

        return new JsonModel($userAccountArray);
    }

    public function create($data)
    {
        // Check that user is logged in.
        if ($this->getAuthenticationService()->hasIdentity()) {
            if ($this->getAuthenticationService()->getIdentity()->isAdmin()) {
                // Verify that email address meets email address standards.
                if (!filter_var($data['user']['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

                    return new JsonModel([
                        'errors' => ['email' => 'Invalid email address'],
                    ]);
                }

                // Verify that password meets OPG password complexity requirements, if supplied.
                if (!empty($data['user']['password'])) {
                    if (!empty($this->userService->verifyPasswordComplexity($data['user']['password']))) {
                        $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

                        return new JsonModel([
                            'errors' => ['password' => 'Password does not meet complexity requirement'],
                        ]);
                    }
                }

                // Verify that roles array is indeed an array.
                if (!is_array($data['user']['roles'])) {
                    $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

                    return new JsonModel([
                        'errors' => ['roles' => 'Roles must be specified as an array'],
                    ]);
                }

                // Attempt to persist new user.
                try {
                    $newUser = $this->userCreationService->createUser(
                        $data['user']['email'],
                        $data['user']['password'],
                        in_array('System Admin', $data['user']['roles'], true)
                    );
                } catch (UserAlreadyExistsException $exception) {
                    $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);

                    return new JsonModel([
                        'errors' => ['email' => $exception->getMessage()],
                    ]);
                }

                // New user has been successfully persisted.
                $this->getResponse()->setStatusCode(Response::STATUS_CODE_201);

                return new JsonModel([
                    'email' => $newUser->getEmail(),
                ]);
            }
        }

        // User is not authorised to create new users.
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_402);

        return new JsonModel([
            'error' => 'Invalid credentials',
        ]);
    }

    public function update($id, $data)
    {
        if ($this->getAuthenticationService()->hasIdentity()) {
            $userAccount = $this->getAuthenticationService()->getIdentity();
            if ($userAccount->isAdmin() || $this->currentlyLoggedInUserIsUpdatingTheirProfile($id, $userAccount)) {
                $userUpdateServiceResponse = $this->userUpdateService->updateUser($id, $data);
                $this->getResponse()->setStatusCode($userUpdateServiceResponse['status']);

                return new JsonModel($userUpdateServiceResponse['body']);
            }
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);

        return new JsonModel();
    }


    private function currentlyLoggedInUserIsUpdatingTheirProfile($id, $userAccount)
    {
        if ($id == $userAccount->getId()) {
            return true;
        }

        return false;
    }

    /**
     * @param int|string $id
     * @param mixed $data
     * @return array<mixed>|JsonModel<mixed>
     */
    public function patch($id, $data)
    {
        // Scenario 1: Setting own password via a single-use token.
        $oneTimePasswordSetTokenHeader = $this->getRequest()->getHeaders('Sirius-One-Time-Password-Set-Token', false);

        if ($oneTimePasswordSetTokenHeader instanceof HeaderInterface) {
            $oneTimePasswordSetToken = $oneTimePasswordSetTokenHeader->getFieldValue();
            $updateErrors = $this->userService->setPasswordForUserViaOneTimeToken(
                $oneTimePasswordSetToken,
                $id,
                $data['password']
            );
            $this->getResponse()->setStatusCode($updateErrors['status']);

            if ($updateErrors['status'] == 200) {
                $this->securityLogger->passwordUpdateViaSingleUseTokenSuccessful($id);
            } else {
                $this->securityLogger->passwordUpdateViaSingleUseTokenFailed($id, $updateErrors['errors']['user'] ?? null);
            }

            return new JsonModel($updateErrors['errors']);
        }

        // Scenario 2: Setting own password by being logged in and supplying existing password.
        if (!$this->getAuthenticationService()->hasIdentity()) {
            $this->logger->err(
                'Attempting to update user account without authorisation',
                [
                    'category' => 'Security',
                    'subcategory' => 'User password change',
                    'userId' => $id,
                ]
            );

            $updateErrors = [
                'status' => Response::STATUS_CODE_401,
                'errors' => [
                    'user' => 'Attempting to update user account without authorisation',
                ],
            ];
            $this->getResponse()->setStatusCode($updateErrors['status']);

            return new JsonModel($updateErrors['errors']);
        }

        /** @var UserAccount $userAccount */
        $userAccount = $this->getAuthenticationService()->getIdentity();

        // Verify that user is changing own account.
        if (intval($id) !== $userAccount->getId()) {
            $this->logger->err(
                'User attempting to change password on account that is not their own',
                [
                    'category' => 'Security',
                    'subcategory' => 'User password change',
                    'loggedInUserId' => $id,
                    'accountToChange' => $userAccount->getId(),
                ]
            );

            $updateErrors = [
                'status' => Response::STATUS_CODE_401,
                'errors' => [
                    'user' => 'User attempting to change password on account that is not their own',
                ],
            ];
            $this->getResponse()->setStatusCode($updateErrors['status']);

            return new JsonModel($updateErrors['errors']);
        }

        $existingPasswordHeader = $this->getRequest()->getHeaders('Sirius-Existing-Password', false);
        if (!$existingPasswordHeader instanceof HeaderInterface) {
            $this->logger->err(
                'Existing password was not supplied and thus could not be verified',
                [
                    'category' => 'Security',
                    'subcategory' => 'User password change',
                    'userId' => $id,
                ]
            );

            $updateErrors = [
                'status' => Response::STATUS_CODE_400,
                'errors' => [
                    'password' => 'Enter your current password',
                ],
            ];
            $this->getResponse()->setStatusCode($updateErrors['status']);

            return new JsonModel($updateErrors['errors']);
        }

        $existingPassword = $existingPasswordHeader->getFieldValue();
        $updateErrors = $this->userService->setPasswordForUserViaExistingPassword(
            $userAccount,
            $existingPassword,
            $data['password']
        );
        $this->getResponse()->setStatusCode($updateErrors['status']);

        if ($updateErrors['status'] == 200) {
            $this->securityLogger->passwordUpdateViaSuppliedPasswordSuccessful($id);
        } else {
            $this->securityLogger->passwordUpdateViaSuppliedPasswordFailed($id, $updateErrors['errors']);
        }

        return new JsonModel($updateErrors['errors']);
    }

    public function delete($id)
    {
        if ($this->getAuthenticationService()->hasIdentity()) {
            $userAccount = $this->getAuthenticationService()->getIdentity();
            if ($userAccount->isAdmin()) {
                $this->userUpdateService->deleteUser($id);

                $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
                return new JsonModel([]);
            }
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);

        return new JsonModel();
    }

    private function getAuthenticationService(): AuthenticationServiceInterface
    {
        return $this->hasBypassMembraneHeader()
            ? $this->authenticationServiceConstructor->getBypassMembrane()
            : $this->authenticationServiceConstructor->getNormal();
    }
}
