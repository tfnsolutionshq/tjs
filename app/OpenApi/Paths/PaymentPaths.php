<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class PaymentPaths
{
    #[OA\Get(
        path: '/me/memberships',
        operationId: 'membershipsIndex',
        summary: 'Active memberships and available plans',
        security: [['sanctum' => []]],
        tags: ['Memberships'],
        parameters: [
            new OA\Parameter(name: 'scope', in: 'query', schema: new OA\Schema(type: 'string', enum: ['featured', 'all'], default: 'featured')),
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Membership catalog'),
        ]
    )]
    public function memberships(): void
    {
    }

    #[OA\Get(
        path: '/me/payments',
        operationId: 'paymentsIndex',
        summary: 'Payment transaction history',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated transactions'),
        ]
    )]
    public function paymentsIndex(): void
    {
    }

    #[OA\Get(
        path: '/me/payments/{paymentTransaction}',
        operationId: 'paymentsShow',
        summary: 'Payment transaction detail',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'paymentTransaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transaction detail'),
            new OA\Response(response: 403, description: 'Not your transaction'),
        ]
    )]
    public function paymentsShow(): void
    {
    }

    #[OA\Get(
        path: '/me/payments/{paymentTransaction}/receipt',
        operationId: 'paymentsReceipt',
        summary: 'Download PDF receipt',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'paymentTransaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF file', content: new OA\MediaType(mediaType: 'application/pdf')),
            new OA\Response(response: 404, description: 'Receipt not available', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
        ]
    )]
    public function paymentsReceipt(): void
    {
    }

    #[OA\Post(
        path: '/me/payments/verify',
        operationId: 'paymentsVerify',
        summary: 'Confirm payment after Paystack checkout',
        description: 'Call after the user completes Paystack checkout to grant entitlements (purchase, membership, fees).',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reference'],
                properties: [
                    new OA\Property(property: 'reference', type: 'string', example: 'TJS_abc123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Payment confirmed'),
            new OA\Response(response: 422, description: 'Verification failed'),
        ]
    )]
    public function paymentsVerify(): void
    {
    }

    #[OA\Post(
        path: '/me/payments/articles/{journal}/{article}/purchase',
        operationId: 'purchaseArticle',
        summary: 'Initialize Paystack checkout for paid article',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'article', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paystack initialize payload'),
        ]
    )]
    public function purchaseArticle(): void
    {
    }

    #[OA\Post(
        path: '/me/payments/memberships/{plan}/purchase',
        operationId: 'purchaseMembership',
        summary: 'Initialize Paystack checkout for membership plan',
        security: [['sanctum' => []]],
        tags: ['Payments', 'Memberships'],
        parameters: [
            new OA\Parameter(name: 'plan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paystack initialize payload'),
        ]
    )]
    public function purchaseMembership(): void
    {
    }

    #[OA\Post(
        path: '/me/payments/journals/{journal}/activate',
        operationId: 'activateJournal',
        summary: 'Initialize Paystack checkout for journal activation',
        security: [['sanctum' => []]],
        tags: ['Payments', 'Journal Manage'],
        parameters: [
            new OA\Parameter(name: 'journal', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paystack initialize payload'),
            new OA\Response(response: 403, description: 'Not a journal manager'),
        ]
    )]
    public function activateJournal(): void
    {
    }
}
