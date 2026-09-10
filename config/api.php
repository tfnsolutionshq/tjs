<?php

return [
    /*
    | Mobile / external API (routes/api.php). Disable to return 503 on all /api/* routes.
    */
    'enabled' => filter_var(env('API_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
