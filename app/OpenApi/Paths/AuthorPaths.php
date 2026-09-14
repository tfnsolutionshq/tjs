<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class AuthorPaths
{
    #[OA\Get(
        path: '/me/submissions/create-options',
        operationId: 'authorSubmissionCreateOptions',
        summary: 'Open calls and categories for new submission',
        security: [['sanctum' => []]],
        tags: ['Author'],
        responses: [
            new OA\Response(response: 200, description: 'Create options'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Email not verified'),
        ]
    )]
    public function createOptions(): void
    {
    }

    #[OA\Get(
        path: '/me/submissions',
        operationId: 'authorSubmissionsIndex',
        summary: 'List own submissions',
        security: [['sanctum' => []]],
        tags: ['Author'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 12)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated submissions with stats'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Post(
        path: '/me/submissions',
        operationId: 'authorSubmissionsStore',
        summary: 'Create submission',
        description: 'Multipart upload. Required file field: `document` (DOC/DOCX, max 51200 KB).',
        security: [['sanctum' => []]],
        tags: ['Author'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['announcement_id', 'title', 'document'],
                    properties: [
                        new OA\Property(property: 'announcement_id', type: 'integer'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'abstract', type: 'string'),
                        new OA\Property(property: 'category', type: 'string'),
                        new OA\Property(property: 'keywords', type: 'string'),
                        new OA\Property(property: 'document', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Submission created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Get(
        path: '/me/submissions/{submission}',
        operationId: 'authorSubmissionsShow',
        summary: 'Submission detail',
        security: [['sanctum' => []]],
        tags: ['Author'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Submission detail'),
            new OA\Response(response: 403, description: 'Not your submission'),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Post(
        path: '/me/submissions/{submission}/resubmit',
        operationId: 'authorSubmissionsResubmit',
        summary: 'Upload revision',
        security: [['sanctum' => []]],
        tags: ['Author'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['document'],
                    properties: [
                        new OA\Property(property: 'document', type: 'string', format: 'binary'),
                        new OA\Property(property: 'notes', type: 'string'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Revision uploaded'),
        ]
    )]
    public function resubmit(): void
    {
    }

    #[OA\Post(
        path: '/me/submissions/{submission}/pay-submission-fee',
        operationId: 'authorPaySubmissionFee',
        summary: 'Initialize Paystack checkout for submission fee',
        security: [['sanctum' => []]],
        tags: ['Author', 'Payments'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paystack initialize payload', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/PaystackInitialize'),
            ])),
        ]
    )]
    public function paySubmissionFee(): void
    {
    }

    #[OA\Post(
        path: '/me/submissions/{submission}/pay-publication-fee',
        operationId: 'authorPayPublicationFee',
        summary: 'Initialize Paystack checkout for publication fee (APC)',
        security: [['sanctum' => []]],
        tags: ['Author', 'Payments'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paystack initialize payload'),
        ]
    )]
    public function payPublicationFee(): void
    {
    }
}
