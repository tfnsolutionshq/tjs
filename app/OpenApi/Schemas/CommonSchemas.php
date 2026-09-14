<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MessageResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'OK'),
    ]
)]
#[OA\Schema(
    schema: 'NotFoundError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Journal not found.'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaystackInitialize',
    properties: [
        new OA\Property(property: 'reference', type: 'string', example: 'TJS_abc123'),
        new OA\Property(property: 'authorization_url', type: 'string', format: 'uri'),
        new OA\Property(property: 'access_code', type: 'string'),
        new OA\Property(property: 'amount', type: 'integer', example: 5000),
        new OA\Property(property: 'currency', type: 'string', example: 'NGN'),
        new OA\Property(property: 'gateway_mode', type: 'string', enum: ['personal', 'split', 'platform']),
        new OA\Property(property: 'purpose', type: 'string', example: 'article_purchase'),
        new OA\Property(property: 'transaction_id', type: 'integer', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'JournalSummary',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'is_featured', type: 'boolean'),
    ]
)]
class CommonSchemas
{
}
