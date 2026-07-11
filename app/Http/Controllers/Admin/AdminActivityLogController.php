<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        /*
         * Bound the search string before running wildcard LIKE queries.
         * Eloquent still parameter-binds the value; max:100 is mainly an
         * abuse/performance control against extremely large search payloads.
         */
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $logs = AdminActivityLog::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query
                    ->where('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'search' => $search,
        ]);
    }
}
