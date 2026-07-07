@extends('admin.layouts.app', ['title' => 'Enrollment Queue | MCARE Admin'])

@section('content')
    @php
        $badgeClasses = [
            'profile_submitted' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'pre_enlistment' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'denied' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $paymentBadgeClasses = [
            'not_selected' => 'bg-slate-50 text-slate-700 ring-slate-100',
            'onsite_pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'online_pending' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'expired' => 'bg-red-50 text-red-700 ring-red-100',
        ];
    @endphp

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">
        <div class="dashboard-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="dashboard-pill bg-white/15 text-white ring-white/20">Enrollment admin</span>
                    <h1 class="mt-4">Applicant queue</h1>
                    <p>
                        Review submitted Caregiving NC II learner profiles and move qualified applicants into pre-enlistment, approval, or denial.
                    </p>
                </div>
                <div class="rounded-2xl bg-white/15 px-5 py-4 text-right ring-1 ring-white/20">
                    <p class="text-xs font-black uppercase tracking-wide text-white/65">Total applications</p>
                    <p class="mt-1 font-display text-3xl font-black text-white">{{ $totalApplications }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.enrollments.index') }}" class="mt-8 grid grid-cols-1 gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 md:grid-cols-[1fr_220px_auto]">
                <div>
                    <label for="search" class="mb-2 block text-xs font-bold uppercase text-slate-500">Search</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Name, email, or contact number" class="form-field bg-white">
                </div>
                <div>
                    <label for="status" class="mb-2 block text-xs font-bold uppercase text-slate-500">Status</label>
                    <select id="status" name="status" class="form-field bg-white">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status => $label)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="primary-action w-full md:w-auto">
                        Filter
                    </button>
                    <a href="{{ route('admin.enrollments.index') }}" class="secondary-action">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <aside class="dashboard-panel">
            <p class="dashboard-section-kicker">Status summary</p>
            <div class="mt-5 space-y-3">
                @foreach ($statuses as $status => $label)
                    <a href="{{ route('admin.enrollments.index', ['status' => $status]) }}" class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 hover:border-purple-100 hover:bg-purple-50">
                        <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                        <span class="rounded-full bg-white px-3 py-1 text-sm font-bold text-slate-900">{{ $counts[$status] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
        </aside>
    </section>

    <section class="mt-8 overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xl shadow-slate-200/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Applicant</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Program</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Batch</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Schedule</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Payment</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Submitted</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($applications as $application)
                        <tr class="hover:bg-purple-50/40">
                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-900">{{ $application->last_name }}, {{ $application->first_name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $application->email }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $application->contact_number }}</p>
                            </td>
                            <td class="px-6 py-5 text-sm font-semibold text-slate-700">{{ $application->program }}</td>
                            <td class="px-6 py-5 text-sm text-slate-600">{{ $application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned' }}</td>
                            <td class="px-6 py-5 text-sm text-slate-600">{{ $application->schedule_preference }}</td>
                            <td class="px-6 py-5">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $badgeClasses[$application->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                    {{ $application->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                    {{ $application->paymentStatusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-sm text-slate-500">{{ $application->created_at?->format('M d, Y') }}</td>
                            <td class="px-6 py-5 text-right">
                                <a href="{{ route('admin.enrollments.show', $application) }}" class="inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-4 py-2 text-sm font-bold text-purple-700 hover:bg-purple-50">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-14 text-center">
                                <p class="text-lg font-bold text-slate-900">No applications found</p>
                                <p class="mt-2 text-sm text-slate-500">Try adjusting the search or status filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($applications->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $applications->links() }}
            </div>
        @endif
    </section>
@endsection
