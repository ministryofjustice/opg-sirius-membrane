<?php

return [
    'notify' => [
        'api_key' => getenv("OPG_CORE_MEMBRANE_NOTIFY_API_KEY") ?: '',
    ]
];
