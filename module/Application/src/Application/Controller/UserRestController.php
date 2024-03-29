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

    public function __construct(
        private readonly AuthenticationServiceConstructor $authenticationServiceConstructor,
        private readonly UserService $userService,
        private readonly UserCreationService $userCreationService,
        private readonly UserUpdateService $userUpdateService,
        private readonly LoggerInterface $logger,
        private readonly SecurityLogger $securityLogger
    ) {
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

    /**
     * @param mixed[] $data
     */
    public function create(mixed $data)
    {
        // Check that user is logged in.
        if (!$this->getAuthenticationService()->hasIdentity() || !$this->getAuthenticationService()->getIdentity()->isAdmin()) {
            // User is not authorised to create new users.
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_402);

            return new JsonModel([
                'error' => 'Invalid credentials',
            ]);
        }

        $errors = [];

        if (!filter_var($data['user']['email'], FILTER_VALIDATE_EMAIL)) {
            // Verify that email address meets email address standards.
            $errors['email'] = 'Invalid email address';
        } elseif (!empty($data['user']['password']) && !empty($this->userService->verifyPasswordComplexity($data['user']['password']))) {
            // Verify that password meets OPG password complexity requirements, if supplied.
            $errors['password'] = 'Password does not meet complexity requirement';
        } elseif (!is_array($data['user']['roles'])) {
            // Verify that roles array is indeed an array.
            $errors['roles'] = 'Roles must be specified as an array';
        } else {
            // Attempt to persist new user.
            try {
                $newUser = $this->userCreationService->createUser(
                    $data['user']['email'],
                    $data['user']['password'],
                    in_array('System Admin', $data['user']['roles'], true)
                );
            } catch (UserAlreadyExistsException $exception) {
                $errors['email'] = $exception->getMessage();
            }
        }

        if ($errors || !isset($newUser)) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
            return new JsonModel([
                'errors' => $errors,
            ]);
        } else {
            // New user has been successfully persisted.
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_201);
            return new JsonModel([
                'email' => $newUser->getEmail(),
            ]);
        }
    }

    /**
     * @param mixed[] $data
     */
    public function update(mixed $id, mixed $data)
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
        $oneTimePasswordSetTokenHeader = $this->getRequest()->getHeaders('Sirius-One-Time-Password-Set-Token');

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

    public function delete(mixed $id)
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
