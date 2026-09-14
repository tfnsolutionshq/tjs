<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiNotFoundResponse
{
    public static function make(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => [],
        ], 404);
    }
}
