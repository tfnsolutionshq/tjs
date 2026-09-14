<?php

return [
    /*
    | Mobile / external API (routes/api.php). Disable to return 503 on all /api/* routes.
    */
    'enabled' => filter_var(env('API_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | Swagger UI at /api/documentation (requires API_ENABLED).
    */
    'docs_enabled' => filter_var(env('API_DOCS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
