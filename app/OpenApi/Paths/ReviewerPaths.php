<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class ReviewerPaths
{
    #[OA\Get(
        path: '/me/reviews',
        operationId: 'reviewsIndex',
        summary: 'Active reviewer assignments',
        security: [['sanctum' => []]],
        tags: ['Reviewer'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 12)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Assignment list with stats'),
            new OA\Response(response: 403, description: 'No review queue access'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/me/reviews/{submission}',
        operationId: 'reviewsShow',
        summary: 'Review assignment detail',
        security: [['sanctum' => []]],
        tags: ['Reviewer'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Review detail (author hidden on closed review)'),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Post(
        path: '/me/reviews/{submission}/decide',
        operationId: 'reviewsDecide',
        summary: 'Submit review decision',
        security: [['sanctum' => []]],
        tags: ['Reviewer'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['decision'],
                properties: [
                    new OA\Property(property: 'decision', type: 'string', enum: ['accept', 'reject', 'revision_requested']),
                    new OA\Property(property: 'comment', type: 'string'),
                    new OA\Property(property: 'rejection_reason', type: 'string', description: 'Required when decision is reject'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Decision recorded'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function decide(): void
    {
    }

    #[OA\Get(
        path: '/me/reviews/{submission}/document',
        operationId: 'reviewsDownloadDocument',
        summary: 'Download current manuscript',
        security: [['sanctum' => []]],
        tags: ['Reviewer'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Document file stream'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function downloadDocument(): void
    {
    }

    #[OA\Get(
        path: '/me/reviews/{submission}/revisions/{revision}/document',
        operationId: 'reviewsDownloadRevision',
        summary: 'Download revision document',
        security: [['sanctum' => []]],
        tags: ['Reviewer'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'revision', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Revision file stream'),
        ]
    )]
    public function downloadRevision(): void
    {
    }
}
