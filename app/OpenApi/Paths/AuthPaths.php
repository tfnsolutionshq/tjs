<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class AuthPaths
{
    #[OA\Post(
        path: '/auth/login',
        operationId: 'authLogin',
        summary: 'Login and obtain bearer token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'mobile'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token issued'),
            new OA\Response(response: 422, description: 'Invalid credentials', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Too many attempts'),
            new OA\Response(response: 503, description: 'API disabled'),
        ]
    )]
    public function login(): void
    {
    }

    #[OA\Get(
        path: '/auth/user',
        operationId: 'authUser',
        summary: 'Current user profile and capabilities',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Profile'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function user(): void
    {
    }

    #[OA\Post(
        path: '/auth/logout',
        operationId: 'authLogout',
        summary: 'Revoke current token',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out'),
        ]
    )]
    public function logout(): void
    {
    }

    #[OA\Post(
        path: '/auth/verify-email',
        operationId: 'authVerifyEmail',
        summary: 'Verify email with OTP code',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', minLength: 6, maxLength: 6, example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Verified'),
            new OA\Response(response: 422, description: 'Invalid code'),
        ]
    )]
    public function verifyEmail(): void
    {
    }

    #[OA\Post(
        path: '/auth/resend-verification',
        operationId: 'authResendVerification',
        summary: 'Resend email verification OTP',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Code sent'),
            new OA\Response(response: 429, description: 'Cooldown active'),
        ]
    )]
    public function resendVerification(): void
    {
    }
}
