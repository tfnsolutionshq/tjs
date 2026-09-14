<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class ProductionPaths
{
    #[OA\Get(
        path: '/me/production/queue',
        operationId: 'productionQueueIndex',
        summary: 'Production queue',
        security: [['sanctum' => []]],
        tags: ['Production'],
        parameters: [
            new OA\Parameter(name: 'filter', in: 'query', schema: new OA\Schema(type: 'string', enum: ['awaiting', 'in_production', 'ready', 'completed'], default: 'awaiting')),
            new OA\Parameter(name: 'journal_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Queue list with stats'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/me/production/queue/{submission}',
        operationId: 'productionQueueShow',
        summary: 'Production detail with checklist schema',
        security: [['sanctum' => []]],
        tags: ['Production'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Production detail'),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Post(
        path: '/me/production/queue/{submission}/start',
        operationId: 'productionStart',
        summary: 'Start production',
        security: [['sanctum' => []]],
        tags: ['Production'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Production started'),
        ]
    )]
    public function start(): void
    {
    }

    #[OA\Post(
        path: '/me/production/queue/{submission}/upload',
        operationId: 'productionUpload',
        summary: 'Upload production document',
        security: [['sanctum' => []]],
        tags: ['Production'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['production_document'],
                    properties: [
                        new OA\Property(property: 'production_document', type: 'string', format: 'binary', description: 'DOC, DOCX, or PDF (max 51200 KB)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Document uploaded'),
        ]
    )]
    public function upload(): void
    {
    }

    #[OA\Post(
        path: '/me/production/queue/{submission}/complete',
        operationId: 'productionComplete',
        summary: 'Mark production complete',
        security: [['sanctum' => []]],
        tags: ['Production'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'checklist',
                        type: 'object',
                        description: 'Production checklist booleans (see checklist_schema in detail response)',
                        example: ['formatting_applied' => true, 'branding_applied' => true]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Production complete'),
        ]
    )]
    public function complete(): void
    {
    }

    #[OA\Get(
        path: '/me/production/queue/{submission}/source-document',
        operationId: 'productionDownloadSource',
        summary: 'Download author manuscript',
        security: [['sanctum' => []]],
        tags: ['Production'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Source document stream'),
        ]
    )]
    public function downloadSource(): void
    {
    }

    #[OA\Get(
        path: '/me/production/queue/{submission}/production-document',
        operationId: 'productionDownloadProduction',
        summary: 'Download current production file',
        security: [['sanctum' => []]],
        tags: ['Production'],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Production document stream'),
        ]
    )]
    public function downloadProduction(): void
    {
    }
}
