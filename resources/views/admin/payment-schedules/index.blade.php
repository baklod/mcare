@extends('admin.layouts.app', ['title' => 'Payment Verification | MCARE Admin'])

@section('content')
    @php
        $paymentBadgeClasses = [
            'onsite_pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
            'online_pending' => 'bg-purple-50 text-purple-800 ring-purple-200',
            'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            'expired' => 'bg-red-50 text-red-800 ring-red-200',
        ];
    @endphp

    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Enrollee payments</p>
            <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="dashboard-section-title text-3xl">Payment verification</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Review only payment methods selected by enrollees, confirm references, and record the administrator who verified each decision.</p>
                </div>
                <a href="{{ route('admin.schedules.index') }}" class="secondary-action">View batch deadlines</a>
            </div>
        </header>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach (['Pending verification' => $stats['pending'], 'On-site selected' => $stats['onsite'], 'Online selected' => $stats['online'], 'Verified paid' => $stats['paid']] as $label => $count)
                <article class="dashboard-stat min-h-0">
                    <div>
                        <p class="dashboard-stat-label">{{ $label }}</p>
                        <p class="dashboard-stat-value">{{ $count }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.payment-schedules.index') }}" data-auto-filter class="dashboard-panel grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="md:col-span-2">
                <label for="payment-search" class="mb-2 block text-xs font-bold uppercase text-slate-500">Search enrollee or reference</label>
                <input id="payment-search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-field" placeholder="Name, email, receipt, or checkout reference">
            </div>
            <div>
                <label for="payment-method" class="mb-2 block text-xs font-bold uppercase text-slate-500">Method</label>
                <select id="payment-method" name="method" class="form-field">
                    <option value="">All methods</option>
                    <option value="onsite" @selected(($filters['method'] ?? '') === 'onsite')>On-site</option>
                    <option value="online" @selected(($filters['method'] ?? '') === 'online')>Online</option>
                </select>
            </div>
            <div>
                <label for="payment-status" class="mb-2 block text-xs font-bold uppercase text-slate-500">Status</label>
                <select id="payment-status" name="status" class="form-field">
                    <option value="">All statuses</option>
                    @foreach ($paymentStatuses as $value => $label)
                        @if ($value !== 'not_selected')
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment-batch" class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label>
                <select id="payment-batch" name="batch_id" class="form-field">
                    <option value="">All batches</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch->id }}" @selected((int) ($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment-schedule" class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label>
                <select id="payment-schedule" name="schedule" class="form-field">
                    <option value="">AM and PM</option>
                    <option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option>
                    <option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option>
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
                <button class="primary-action">Apply filters</button>
                <a href="{{ route('admin.payment-schedules.index') }}" class="secondary-action">Reset</a>
            </div>
        </form>

        <section class="dashboard-table-wrap">
            <div class="overflow-x-auto">
                <table class="dashboard-table min-w-[76rem]">
                    <thead>
                        <tr>
                            <th>Enrollee</th>
                            <th>Batch / schedule</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Verification</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $application)
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-950">{{ $application->last_name }}, {{ $application->first_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $application->email }}</p>
                                    <a href="{{ route('admin.enrollments.show', $application) }}" class="mt-2 inline-flex font-semibold text-purple-700 hover:text-purple-900">Open enrollee record</a>
                                </td>
                                <td>
                                    <p class="font-bold text-slate-900">{{ $application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned' }}</p>
                                    <p class="mt-1">{{ $application->schedule_preference }} · {{ $application->batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule pending' }}</p>
                                </td>
                                <td class="font-semibold">{{ str($application->payment_method)->headline() }}</td>
                                <td class="max-w-64 break-all text-xs">{{ $application->payment_receipt_number ?: $application->paymongo_checkout_reference ?: $application->payment_reference ?: 'Reference pending' }}</td>
                                <td class="font-semibold">{{ $application->payment_currency }} {{ number_format((float) $application->payment_amount, 2) }}</td>
                                <td>
                                    <span class="inline-flex rounded-lg px-3 py-1.5 text-xs font-bold ring-1 ring-inset {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $application->paymentStatusLabel() }}</span>
                                    @if ($application->payment_verified_at)
                                        <p class="mt-2 text-xs text-slate-500">By {{ $application->paymentVerifier?->name ?? 'Admin' }}<br>{{ $application->payment_verified_at->format('M d, Y g:i A') }}</p>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.payment-schedules.update', $application) }}" class="min-w-64 space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="payment_verification_notes" rows="2" maxlength="1000" class="form-field" placeholder="Optional verification note">{{ $application->payment_verification_notes }}</textarea>
                                        <div class="flex flex-wrap gap-2">
                                            @if ($application->payment_method === 'onsite')
                                                <button name="action" value="verify_paid" class="min-h-10 rounded-lg bg-emerald-700 px-3 text-xs font-bold text-white hover:bg-emerald-800">Verify paid</button>
                                            @else
                                                <span class="inline-flex min-h-10 items-center rounded-lg border border-sky-200 bg-sky-50 px-3 text-xs font-bold text-sky-800">Auto-verification only</span>
                                            @endif
                                            <button name="action" value="return_pending" class="min-h-10 rounded-lg border border-amber-200 bg-white px-3 text-xs font-bold text-amber-800 hover:bg-amber-50">Pending</button>
                                            <button name="action" value="mark_expired" class="min-h-10 rounded-lg border border-red-200 bg-white px-3 text-xs font-bold text-red-700 hover:bg-red-50">Expire</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-14 text-center">
                                    <p class="font-bold text-slate-950">No selected payment methods found</p>
                                    <p class="mt-2 text-sm text-slate-500">Only enrollees who chose online or on-site payment appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($payments->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $payments->links() }}</div>
            @endif
        </section>
    </section>
@endsection
