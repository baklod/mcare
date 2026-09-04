<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\TrainerSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainerSearchController extends Controller
{
    public function index(Request $request, TrainerSearchService $search): View
    {
        $query = $this->queryFrom($request);
        $groups = $query === '' ? [] : $search->search($request->user(), $query, 20);

        return view('trainer.search', [
            'query' => $query,
            'groups' => $groups,
            'resultCount' => collect($groups)->sum(fn (array $group): int => count($group['results'])),
        ]);
    }

    public function suggest(Request $request, TrainerSearchService $search): JsonResponse
    {
        $query = $this->queryFrom($request);

        return response()->json([
            'query' => $query,
            'groups' => $query === '' ? [] : $search->search($request->user(), $query, 5),
        ]);
    }

    private function queryFrom(Request $request): string
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return trim((string) ($validated['q'] ?? ''));
    }
}
