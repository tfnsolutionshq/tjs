<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'TJS Mobile API',
    description: 'REST API for the TFN Journal System (TJS) mobile and external clients. Authenticated routes use Laravel Sanctum bearer tokens obtained from `POST /auth/login`. Most write endpoints require a verified email address.',
    contact: new OA\Contact(name: 'TFN Solutions', url: 'https://tjs.tfnsolutions.us')
)]
#[OA\Server(url: '/api/v1', description: 'API v1 base path')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    description: 'Sanctum personal access token from `POST /auth/login`. Format: `Bearer {token}`',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]
#[OA\Tag(name: 'Auth', description: 'Login, profile, email verification')]
#[OA\Tag(name: 'Catalog', description: 'Public journals and articles')]
#[OA\Tag(name: 'Author', description: 'Author submissions and fees')]
#[OA\Tag(name: 'Payments', description: 'Paystack checkout initialization and receipts')]
#[OA\Tag(name: 'Memberships', description: 'Membership plans')]
#[OA\Tag(name: 'Reviewer', description: 'Reviewer assignment queue')]
#[OA\Tag(name: 'Production', description: 'Production editor queue')]
#[OA\Tag(name: 'Journal Manage', description: 'Journal editor/admin tools')]
class OpenApiSpec
{
}
