<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class JournalManagePaths
{
    #[OA\Get(
        path: '/me/journals/{journal}/manage/dashboard',
        operationId: 'journalManageDashboard',
        summary: 'Journal manage dashboard',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Journal slug'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stats and recent activity'),
            new OA\Response(response: 403, description: 'Not a manager or activation locked'),
        ]
    )]
    public function dashboard(): void
    {
    }

    #[OA\Get(
        path: '/me/journals/{journal}/manage/submissions',
        operationId: 'journalManageSubmissionsIndex',
        summary: 'Journal submissions inbox',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated submissions with stats'),
        ]
    )]
    public function submissionsIndex(): void
    {
    }

    #[OA\Get(
        path: '/me/journals/{journal}/manage/submissions/form-options',
        operationId: 'journalManageSubmissionsFormOptions',
        summary: 'Reviewer and issue pickers',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Form options'),
        ]
    )]
    public function formOptions(): void
    {
    }

    #[OA\Get(
        path: '/me/journals/{journal}/manage/submissions/{submission}',
        operationId: 'journalManageSubmissionsShow',
        summary: 'Editor submission detail',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Submission detail with author visible'),
        ]
    )]
    public function submissionsShow(): void
    {
    }

    #[OA\Post(
        path: '/me/journals/{journal}/manage/submissions/{submission}/assign-reviewer',
        operationId: 'journalManageAssignReviewer',
        summary: 'Assign reviewer',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reviewer_id'],
                properties: [
                    new OA\Property(property: 'reviewer_id', type: 'integer'),
                    new OA\Property(property: 'priority', type: 'integer', minimum: 1, maximum: 5),
                    new OA\Property(property: 'due_at', type: 'string', format: 'date-time'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reviewer assigned'),
            new OA\Response(response: 422, description: 'Fee pending or validation error'),
        ]
    )]
    public function assignReviewer(): void
    {
    }

    #[OA\Post(
        path: '/me/journals/{journal}/manage/submissions/{submission}/review-type',
        operationId: 'journalManageUpdateReviewType',
        summary: 'Update review type',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['review_type'],
                properties: [
                    new OA\Property(property: 'review_type', type: 'string', enum: ['closed', 'open']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Review type updated'),
        ]
    )]
    public function updateReviewType(): void
    {
    }

    #[OA\Post(
        path: '/me/journals/{journal}/manage/submissions/{submission}/publish',
        operationId: 'journalManagePublish',
        summary: 'Publish submission to issue',
        description: 'Requires production complete (`ready_to_publish`) and a production document on file.',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['issue_id'],
                properties: [
                    new OA\Property(property: 'issue_id', type: 'integer'),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'doi', type: 'string'),
                    new OA\Property(property: 'license', type: 'string'),
                    new OA\Property(property: 'page_range', type: 'string'),
                    new OA\Property(property: 'visibility', type: 'string', enum: ['open', 'members_only', 'paid', 'closed']),
                    new OA\Property(property: 'author_name', type: 'string'),
                    new OA\Property(property: 'authors_text', type: 'string', description: 'One author per line: Name|email|affiliation|orcid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Published article created'),
            new OA\Response(response: 422, description: 'Not ready to publish'),
        ]
    )]
    public function publish(): void
    {
    }

    #[OA\Get(
        path: '/me/journals/{journal}/manage/reviewer-requests',
        operationId: 'journalManageReviewerRequests',
        summary: 'Pending reviewer applications',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pending and recent requests'),
        ]
    )]
    public function reviewerRequests(): void
    {
    }

    #[OA\Post(
        path: '/me/journals/{journal}/manage/reviewer-requests/{reviewerRequest}/approve',
        operationId: 'journalManageApproveReviewerRequest',
        summary: 'Approve reviewer application',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'reviewerRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'admin_note', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Approved'),
        ]
    )]
    public function approveReviewerRequest(): void
    {
    }

    #[OA\Post(
        path: '/me/journals/{journal}/manage/reviewer-requests/{reviewerRequest}/reject',
        operationId: 'journalManageRejectReviewerRequest',
        summary: 'Reject reviewer application',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'reviewerRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'admin_note', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rejected'),
        ]
    )]
    public function rejectReviewerRequest(): void
    {
    }

    #[OA\Get(
        path: '/me/journals/{journal}/manage/announcements',
        operationId: 'journalManageAnnouncementsIndex',
        summary: 'List announcements and calls',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated announcements'),
        ]
    )]
    public function announcementsIndex(): void
    {
    }

    #[OA\Post(
        path: '/me/journals/{journal}/manage/announcements',
        operationId: 'journalManageAnnouncementsStore',
        summary: 'Create announcement or call for submissions',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'title'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', example: 'call_for_submissions'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'summary', type: 'string'),
                    new OA\Property(property: 'body', type: 'string'),
                    new OA\Property(property: 'is_published', type: 'boolean'),
                    new OA\Property(property: 'issue_id', type: 'integer', description: 'Required for calls'),
                    new OA\Property(property: 'opens_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'closes_at', type: 'string', format: 'date-time'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
        ]
    )]
    public function announcementsStore(): void
    {
    }

    #[OA\Post(
        path: '/me/journals/{journal}/manage/announcements/{announcement}/close',
        operationId: 'journalManageAnnouncementsClose',
        summary: 'Close call for submissions',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'announcement', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Call closed'),
        ]
    )]
    public function announcementsClose(): void
    {
    }

    #[OA\Get(
        path: '/me/journals/{journal}/manage/volumes',
        operationId: 'journalManageVolumes',
        summary: 'Volumes and issues tree',
        security: [['sanctum' => []]],
        tags: ['Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Volumes with nested issues'),
        ]
    )]
    public function volumes(): void
    {
    }
}
