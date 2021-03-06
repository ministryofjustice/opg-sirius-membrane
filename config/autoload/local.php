<?php
/**
 * Local Configuration Override
 *
 * This configuration override file is for overriding environment-specific and
 * security-sensitive configuration information. Copy this file without the
 * .dist extension at the end and populate values as needed.
 *
 * @NOTE: This file is ignored from Git by default with the .gitignore included
 * in LaminasSkeletonApplication. This is a good practice, as it prevents sensitive
 * credentials from accidentally being committed into version control.
 */

return [
    'application' => [
        'baseUri' => getenv('OPG_CORE_MEMBRANE_BACKEND_URI')? getenv('OPG_CORE_MEMBRANE_BACKEND_URI'): 'http://api.local',
    ],
];
