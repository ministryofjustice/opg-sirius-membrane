<?php

use Application\Config\SessionSaveHandlerConfigMapper;
use Application\Session\SaveHandler\EncryptedSessionSaveHandler;
use Application\Model\Entity\UserAccount;
use Application\Controller;
use Application\View\Model\XmlModel;
use Laminas\Router\Http;
use Laminas\ApiTools\ContentNegotiation\JsonModel;

return [
    'router' => [
        'routes' => [
            'session-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/sessions[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\SessionRestController::class,
                    ],
                ],
                'priority' => 10,
            ],
            'v1-session-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/v1/sessions[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\V1\SessionRestController::class,
                    ],
                ],
                'priority' => 10,
            ],
            'user-resend-activation-email' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/users/:id/activation-request',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserResendActivationEmailController::class,
                    ],
                ],
                'priority' => 6,
            ],
            'user-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/users[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserRestController::class,
                    ],
                ],
                'priority' => 9,
            ],
            'user-password-reset-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/users/:id/password-reset',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserPasswordResetRestController::class,
                    ],
                ],
                'priority' => 9,
            ],
            'user-status-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/users/:id/status',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserStatusController::class,
                    ],
                ],
                'priority' => 9,
            ],
            // Deprecated Devise routes that return a 410 status code.
            'devise-create-user-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/admin/:id/users',
                    'constraints' => [
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\NotImplementedController::class,
                        'action' => 'index',
                    ],
                ],
                'priority' => 5,
            ],
            'devise-set-password-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/users/password/:id',
                    'constraints' => [
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\NotImplementedController::class,
                        'action' => 'index',
                    ],
                ],
                'priority' => 5,
            ],
            'devise-activate-user-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/users/activation/:id',
                    'constraints' => [
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\NotImplementedController::class,
                        'action' => 'index',
                    ],
                ],
                'priority' => 5,
            ],
            'devise-confirm-user-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/confirmation/:id',
                    'constraints' => [
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\NotImplementedController::class,
                        'action' => 'index',
                    ],
                ],
                'priority' => 5,
            ],
            'devise-unlock-user-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/admin/:id/users/unlock',
                    'constraints' => [
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\NotImplementedController::class,
                        'action' => 'index',
                    ],
                ],
                'priority' => 5,
            ],
            'devise-make-admin-user-service' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/auth/admin/:id/users/admin_user',
                    'constraints' => [
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\NotImplementedController::class,
                        'action' => 'index',
                    ],
                ],
                'priority' => 5,
            ],
            'health-check' => [
                'type' => Http\Literal::class,
                'options' => [
                    'route' => '/auth/health-check',
                    'verb' => 'get',
                    'defaults' => [
                        'controller' => Controller\HealthCheckController::class,
                        'action' => 'index',
                    ],
                ],
                'priority' => 5,
            ],
            'health-check-db-version' => [
                'type' => Http\Literal::class,
                'options' => [
                    'route' => '/auth/health-check/db-version',
                    'verb' => 'get',
                    'defaults' => [
                        'controller' => Controller\HealthCheckController::class,
                        'action' => 'migrationVersion',
                    ],
                ],
                'priority' => 5,
            ],
            'health-check-service-status' => [
                'type' => Http\Literal::class,
                'options' => [
                    'route' => '/auth/health-check/service-status',
                    'verb' => 'get',
                    'defaults' => [
                        'controller' => Controller\HealthCheckController::class,
                        'action' => 'serviceStatus',
                    ],
                ],
                'priority' => 5,
            ],

            // Catch-all route for all non-Membrane API calls.
            'authentication-proxy' => [
                'type' => Http\Wildcard::class,
                'options' => [
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'index',
                    ],
                ],
                'priority' => 0,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\AuthController::class => Controller\Factory\AuthControllerFactory::class,
            Controller\V1\SessionRestController::class => Controller\V1\Factory\SessionRestControllerFactory::class,
            Controller\HealthCheckController::class => Controller\Factory\HealthCheckControllerFactory::class,
            Controller\SessionRestController::class => Controller\Factory\SessionRestControllerFactory::class,
            Controller\UserPasswordResetRestController::class => Controller\Factory\UserPasswordResetRestControllerFactory::class,
            Controller\UserResendActivationEmailController::class => Controller\Factory\UserResendActivationEmailControllerFactory::class,
            Controller\UserRestController::class => Controller\Factory\UserRestControllerFactory::class,
            Controller\UserStatusController::class => Controller\Factory\UserStatusControllerFactory::class,
            Controller\Console\ImportFixturesController::class => Controller\Console\Factory\ImportFixturesControllerFactory::class
        ],
        'invokables' => [
            Controller\NotImplementedController::class => Controller\NotImplementedController::class,
        ],
    ],
    'doctrine' => [
        'authentication' => [
            'orm_default' => [
                'object_manager' => 'Doctrine\ORM\EntityManager',
                'identity_class' => 'Application\Model\Entity\UserAccount',
                'identity_property' => 'email',
                'credential_property' => 'password',
                'credentialCallable' => [UserAccount::class, 'verifyPasswordAndStatus'],
            ],
        ],
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [
                    getcwd() . '/module/Application/src/Application/Model/Entity/',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    'Application\Model\Entity' => __NAMESPACE__ . '_driver',
                ],
            ],
        ],
        'fixtures' => [
            'Application_fixture' => __DIR__ . '/../src/Application/Fixtures',
        ],
    ],
    'api-tools-content-negotiation' => [
        'controllers' => [
            Controller\AuthController::class => [
                JsonModel::class => [
                    'application/json',
                    'application/*+json',
                ],
                XmlModel::class => [
                    'application/xml',
                    'application/*+xml',
                ],
            ],
            Controller\SessionRestController::class => [
                JsonModel::class => [
                    'application/json',
                    'application/*+json',
                ],
                XmlModel::class => [
                    'application/xml',
                    'application/*+xml',
                ],
            ],
        ],
    ],
    'service_manager' => require __DIR__ . '/services.config.php',
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => getenv("OPG_CORE_MEMBRANE_DISPLAY_EXCEPTIONS") ?: 0,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    // Placeholder for console routes
    'console' => [
        'router' => [
            'routes' => [
                'sirius-import-fixtures' => [
                    'options' => [
                        'route' => 'data-fixture:import [--append] [--purge-with-truncate]',
                        'defaults' => [
                            'controller' => Controller\Console\ImportFixturesController::class,
                            'action' => 'import',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'session' => [
        'config' => [
            'save_handler' => EncryptedSessionSaveHandler::class,
            'actual_save_handler' => SessionSaveHandlerConfigMapper::getConfig(), // the underlying save handler that save_handler wraps around
            'options' => [
                'name' => 'membrane',
                'gc_maxlifetime' => getenv('OPG_CORE_SESSION_TIMEOUT') ? intval(getenv('OPG_CORE_SESSION_TIMEOUT')) : 3600,
                'cache_expire' => 3600,
                'use_cookies' => true,
                'cookie_httponly' => true,
                'cookie_secure' => false,
                'remember_me_seconds' => 3600,
                'gc_divisor' => 1,
                'gc_probability' => 1,
            ],
        ],
        'encryption_filter' => [
            // Any Filter implementing EncryptionAlgorithmInterface.
            'adaptor' => 'BlockCipher',
            // Additional configuration required for the Filter.
            'key' => getenv('OPG_CORE_SESSION_ENCRYPTION_KEY') ?
                getenv('OPG_CORE_SESSION_ENCRYPTION_KEY') :
                'THISISAPLACEHOLDERKEYFORDEVELOPMENTENVIRONMENTSONLY1234567890123',
            'algorithm' => getenv('OPG_CORE_SESSION_ENCRYPTION_ALGORITHM')
                ? getenv('OPG_CORE_SESSION_ENCRYPTION_ALGORITHM') :
                'aes',
            'mode' => 'cbc',
        ],
    ],
    'user_service' => [
        // Default of one day. PHP DateInterval interval_spec period designator expected.
        'one_time_password_set_lifetime' => 'P1D',
        // Used it for generating URLs for embedding in emails.
        'email_system_base_url' => getenv("OPG_CORE_MEMBRANE_EMAIL_BASE_URL") ?: 'https://live.sirius-opg.uk',
        // Number of unsuccessful login attempts before account locking.
        'unsuccessful_login_attempts_permitted' => 3
    ],
];
