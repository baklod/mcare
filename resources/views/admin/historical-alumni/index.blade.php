@extends('admin.layouts.app', ['title' => 'Alumni Claims | MCARE Admin'])

@section('content')
    @php
        $badgeClasses = [
            \App\Models\HistoricalAlumniClaim::STATUS_APPROVED => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            \App\Models\HistoricalAlumniClaim::STATUS_REJECTED => 'bg-red-50 text-red-700 ring-red-100',
            \App\Models\HistoricalAlumniClaim::STATUS_PENDING_EMAIL => 'bg-amber-50 text-amber-700 ring-amber-100',
            \App\Models\HistoricalAlumniClaim::STATUS_PENDING_ONSITE => 'bg-purple-50 text-purple-700 ring-purple-100',
        ];
        $statusIcons = [
            \App\Models\HistoricalAlumniClaim::STATUS_PENDING_EMAIL => ['icon' => 'bell', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'],
            \App\Models\HistoricalAlumniClaim::STATUS_PENDING_ONSITE => ['icon' => 'user-check', 'tone' => 'bg-purple-50 text-purple-700 ring-purple-100'],
            \App\Models\HistoricalAlumniClaim::STATUS_APPROVED => ['icon' => 'circle-check', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
            \App\Models\HistoricalAlumniClaim::STATUS_REJECTED => ['icon' => 'xmark', 'tone' => 'bg-red-50 text-red-700 ring-red-100'],
        ];
    @endphp

    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="dashboard-section-kicker">Historical alumni verification</p>
                <h1 class="dashboard-section-title mt-2 text-2xl">Alumni claims</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Review claims from graduates who trained before the MCARE website. Approve only after checking a valid ID, original COTC/TOR, and MCARE archive records.</p>
            </div>
            <a href="{{ route('alumni.claim.create') }}" class="primary-action inline-flex items-center justify-center gap-2">
                <x-dashboard-icon name="user-check" class="h-4 w-4" />Start alumni claim
            </a>
        </header>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
            <a href="{{ route('admin.historical-alumni.index') }}" class="group flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-purple-300 hover:shadow-sm @if($selectedStatus === '') ring-2 ring-purple-500 border-purple-300 bg-purple-50/20 @endif">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-purple-700">Total claims</span>
                    <span class="mt-2 block font-display text-2xl font-extrabold text-slate-900">{{ $totalClaims }}</span>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><x-dashboard-icon name="user-check" /></span>
            </a>
            @foreach ($statuses as $status => $label)
                <a href="{{ route('admin.historical-alumni.index', ['status' => $status, 'search' => $search]) }}" class="group flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-purple-300 hover:shadow-sm @if($selectedStatus === $status) ring-2 ring-purple-500 border-purple-300 bg-purple-50/20 @endif">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-purple-700">{{ $label }}</span>
                        <span class="mt-2 block font-display text-2xl font-extrabold text-slate-900">{{ $counts[$status] ?? 0 }}</span>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg ring-1 {{ $statusIcons[$status]['tone'] ?? 'bg-slate-50 text-slate-600 ring-slate-100' }}"><x-dashboard-icon :name="$statusIcons[$status]['icon'] ?? 'circle-question'" /></span>
                </a>
            @endforeach
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.historical-alumni.index') }}" data-auto-filter class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="search" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Search</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Name, email, batch, COTC, or TOR" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                </div>
                <div>
                    <label for="status" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status => $label)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Claimant</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Completed</th>
                        <th class="px-4 py-3">Evidence</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($claims as $claim)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-user-avatar :user="$claim->user" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800" />
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $claim->user->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $claim->historical_batch_name ?: 'Batch not known' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="truncate text-slate-700">{{ $claim->user->email }}</p>
                                <p class="text-xs font-semibold {{ $claim->user->hasVerifiedEmail() ? 'text-emerald-700' : 'text-amber-700' }}">{{ $claim->user->hasVerifiedEmail() ? 'Verified' : 'Pending' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $claim->training_completion_year }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ str($claim->evidence_type)->headline() }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $badgeClasses[$claim->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">{{ $claim->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $claim->created_at?->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.historical-alumni.show', $claim) }}" class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">
                                    <x-dashboard-icon name="eye" class="h-3.5 w-3.5" />
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-14 text-center text-slate-500">No alumni claims match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($claims->hasPages())
                <div class="border-t border-slate-100 px-4 py-4">{{ $claims->links() }}</div>
            @endif
        </div>
    </div>
@endsection
