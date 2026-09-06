<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Journal\JournalPickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalPickerController extends Controller
{
    public function __invoke(Request $request, JournalPickerService $picker): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:login,register'],
            'redirect' => ['nullable', 'string', 'max:2048'],
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'mode' => ['nullable', 'in:featured,other,all'],
        ]);

        $redirect = (string) ($data['redirect'] ?? '');
        if ($redirect !== '' && ! str_starts_with($redirect, url('/'))) {
            $redirect = '';
        }

        $mode = $data['mode'] ?? 'all';
        $featuredOnly = $mode === 'featured';
        $nonFeaturedOnly = $mode === 'other';

        return response()->json(
            $picker->search(
                $data['action'],
                $redirect !== '' ? $redirect : null,
                $data['q'] ?? null,
                (int) ($data['page'] ?? 1),
                $featuredOnly,
                $nonFeaturedOnly,
            )
        );
    }
}
