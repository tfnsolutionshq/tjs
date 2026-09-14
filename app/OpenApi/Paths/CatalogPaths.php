<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class CatalogPaths
{
    #[OA\Get(
        path: '/journals',
        operationId: 'listJournals',
        summary: 'List public journals',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 12)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated journal list'),
        ]
    )]
    public function journalsIndex(): void
    {
    }

    #[OA\Get(
        path: '/journals/picker',
        operationId: 'journalsPicker',
        summary: 'Searchable journal picker',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Search by title, subtitle, slug, or initials', schema: new OA\Schema(type: 'string', example: 'unizik')),
            new OA\Parameter(name: 'mode', in: 'query', schema: new OA\Schema(type: 'string', enum: ['featured', 'other', 'all'], default: 'all')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Journal picker results'),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function journalsPicker(): void
    {
    }

    #[OA\Get(
        path: '/journals/{journal}',
        operationId: 'showJournal',
        summary: 'Journal detail',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Journal slug'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Journal detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function journalShow(): void
    {
    }

    #[OA\Get(
        path: '/journals/{journal}/browse',
        operationId: 'browseJournal',
        summary: 'Browse articles in a journal',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'year', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated articles'),
        ]
    )]
    public function journalBrowse(): void
    {
    }

    #[OA\Get(
        path: '/articles',
        operationId: 'listArticles',
        summary: 'Platform-wide public article catalog',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 12)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated articles'),
        ]
    )]
    public function articlesIndex(): void
    {
    }

    #[OA\Get(
        path: '/journals/{journal}/articles/{article}',
        operationId: 'showArticle',
        summary: 'Article detail with access hints',
        description: 'Optional Bearer token improves access resolution for members-only or purchased content.',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Article detail'),
            new OA\Response(response: 404, description: 'Not found or closed'),
        ]
    )]
    public function articleShow(): void
    {
    }
}
