<?php

namespace App\Http\Controllers\Api\V1\JournalManage;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VolumeResource;
use App\Models\Journal;
use Illuminate\Http\JsonResponse;

class VolumeController extends Controller
{
    public function index(Journal $journal): JsonResponse
    {
        $volumes = $journal->volumes()
            ->with(['issues' => fn ($q) => $q->orderBy('issue_number')])
            ->withCount('issues')
            ->orderByDesc('year')
            ->orderByDesc('volume_number')
            ->get();

        $stats = [
            'volumes' => $volumes->count(),
            'issues' => $volumes->sum('issues_count'),
            'published' => $volumes->where('status', 'published')->count(),
            'draft' => $volumes->where('status', 'draft')->count(),
        ];

        return response()->json([
            'data' => VolumeResource::collection($volumes)->resolve(),
            'meta' => ['stats' => $stats],
        ]);
    }
}
