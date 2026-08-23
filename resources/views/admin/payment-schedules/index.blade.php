@extends('admin.layouts.app', ['title' => 'Payment Management & Ledger | MCARE Admin'])

@section('content')
    @php
        $paymentBadgeClasses = [
            'onsite_pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
            'online_pending' => 'bg-purple-50 text-purple-800 ring-purple-200',
            'partially_paid' => 'bg-sky-50 text-sky-800 ring-sky-200',
            'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            'expired' => 'bg-red-50 text-red-800 ring-red-200',
        ];
        $firstApp = $allApplications->first();
    @endphp

    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Tuition & Financial Management</p>
            <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="dashboard-section-title text-3xl">Payment Verification & Ledger</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                        Record on-site tuition transactions, verify physical official receipts, track remaining balances (₱22,000 program fee), and manage trainee payment milestones.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" data-dashboard-dialog-open="record-onsite-payment-dialog" class="primary-action">
                        <x-dashboard-icon name="plus" class="h-4 w-4" />
                        <span>Record On-Site Payment</span>
                    </button>
                    <a href="{{ route('admin.announcements.index') }}" class="secondary-action">
                        <x-dashboard-icon name="bullhorn" class="h-4 w-4" />
                        <span>Send Payment Reminder</span>
                    </a>
                </div>
            </div>
        </header>

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Financial Stats -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <article class="dashboard-stat min-h-0">
                <div>
                    <p class="dashboard-stat-label">Total Tuition Collected</p>
                    <p class="dashboard-stat-value text-emerald-700">₱{{ number_format((float) $stats['total_collected'], 2) }}</p>
                </div>
            </article>
            <article class="dashboard-stat min-h-0">
                <div>
                    <p class="dashboard-stat-label">Fully Paid Trainees</p>
                    <p class="dashboard-stat-value">{{ $stats['fully_paid'] }}</p>
                </div>
            </article>
            <article class="dashboard-stat min-h-0">
                <div>
                    <p class="dashboard-stat-label">Partially Paid (Downpayment)</p>
                    <p class="dashboard-stat-value text-sky-700">{{ $stats['partially_paid'] }}</p>
                </div>
            </article>
            <article class="dashboard-stat min-h-0">
                <div>
                    <p class="dashboard-stat-label">Pending On-Site Tickets</p>
                    <p class="dashboard-stat-value text-amber-700">{{ $stats['pending_tickets'] }}</p>
                </div>
            </article>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.payment-schedules.index') }}" data-auto-filter class="dashboard-panel grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="md:col-span-2">
                <label for="payment-search" class="mb-2 block text-xs font-bold uppercase text-slate-500">Search enrollee or OR #</label>
                <input id="payment-search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-field" placeholder="Name, email, OR receipt number, or reference">
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
                <button type="submit" class="primary-action">Apply filters</button>
                <a href="{{ route('admin.payment-schedules.index') }}" class="secondary-action">Reset</a>
            </div>
        </form>

        <!-- Payments Table -->
        <section class="dashboard-table-wrap">
            <div class="overflow-x-auto">
                <table class="dashboard-table min-w-[78rem]">
                    <thead>
                        <tr>
                            <th>Enrollee</th>
                            <th>Batch / Schedule</th>
                            <th>Tuition Breakdown</th>
                            <th>Latest Reference</th>
                            <th>Payment Status</th>
                            <th>On-Site Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $application)
                            @php
                                $totalFee = (float) ($application->total_program_fee ?? 22000.00);
                                $totalPaid = (float) ($application->total_paid_amount ?? 0.00);
                                $balance = $application->remainingBalance();
                                $appFullName = $application->first_name . ' ' . $application->last_name;
                                $pendingTicket = $application->paymentTransactions->first(fn ($transaction) => $transaction->isOnsiteTicket());
                            @endphp
                            <tr class="align-top">
                                <td>
                                    <p class="font-bold text-slate-950">{{ $application->last_name }}, {{ $application->first_name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $application->email }}</p>
                                    <div class="mt-2 flex items-center gap-3">
                                        <a href="{{ route('admin.enrollments.show', $application) }}" class="text-xs font-semibold text-purple-700 hover:text-purple-900">View profile</a>
                                    </div>
                                </td>
                                <td>
                                    <p class="font-bold text-slate-900">{{ $application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned' }}</p>
                                    <p class="mt-1 text-xs text-slate-600">{{ $application->schedule_preference }} · {{ $application->batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule pending' }}</p>
                                </td>
                                <td>
                                    <div class="space-y-1 text-xs">
                                        <div class="flex justify-between gap-4">
                                            <span class="text-slate-500">Program Fee:</span>
                                            <span class="font-bold text-slate-900">₱{{ number_format($totalFee, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between gap-4">
                                            <span class="text-slate-500">Total Paid:</span>
                                            <span class="font-bold text-emerald-700">₱{{ number_format($totalPaid, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-1">
                                            <span class="text-slate-500">Balance:</span>
                                            <span class="font-bold {{ $balance <= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                                                ₱{{ number_format($balance, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-xs">
                                    @if ($pendingTicket)
                                        <p class="font-mono font-bold text-purple-900">{{ $pendingTicket->ticket_number }}</p>
                                        <p class="mt-1 text-[11px] font-semibold text-amber-700">On-site ticket · ₱{{ number_format((float) $pendingTicket->amount, 2) }}</p>
                                    @else
                                        <p class="font-mono font-bold text-slate-900">
                                            {{ $application->payment_receipt_number ?: $application->paymongo_checkout_reference ?: $application->payment_reference ?: 'Reference pending' }}
                                        </p>
                                    @endif
                                    <p class="mt-1 text-[11px] text-slate-500">
                                        Channel: <span class="font-semibold text-slate-700">{{ str($application->payment_method)->headline() }}</span>
                                    </p>
                                </td>
                                <td>
                                    <span class="inline-flex rounded-lg px-3 py-1.5 text-xs font-bold ring-1 ring-inset {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                        {{ $application->paymentStatusLabel() }}
                                    </span>
                                    @if ($application->payment_verified_at)
                                        <p class="mt-1.5 text-[11px] text-slate-500">
                                            Verified by {{ $application->paymentVerifier?->name ?? 'Admin' }}<br>
                                            {{ $application->payment_verified_at->format('M d, Y') }}
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-col gap-2">
                                        <button type="button" class="inline-flex items-center justify-center gap-1 rounded-lg bg-purple-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-purple-800 transition" data-record-for-app data-app-id="{{ $application->id }}" data-app-name="{{ $appFullName }}" data-app-balance="{{ $balance }}">
                                            <x-dashboard-icon name="plus" class="h-3.5 w-3.5" />
                                            <span>Record Payment</span>
                                        </button>

                                        <!-- Fast Status Toggle -->
                                        <form method="POST" action="{{ route('admin.payment-schedules.update', $application) }}" class="flex flex-wrap items-center gap-1.5">
                                            @csrf
                                            @method('PATCH')
                                            @if ($totalPaid <= 0)
                                                <button name="action" value="return_pending" title="Return to pending verification" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                                                    Pending
                                                </button>
                                                <button name="action" value="mark_expired" title="Mark payment expired" class="rounded-md border border-rose-200 bg-white px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-50">
                                                    Expire
                                                </button>
                                            @else
                                                <span class="text-[11px] font-semibold text-slate-500">Status follows the verified ledger.</span>
                                            @endif
                                        </form>
                                    </div>

                                    <!-- Collapsible Transaction Ledger -->
                                    @if ($application->paymentTransactions->isNotEmpty())
                                        <details class="group mt-3 rounded-lg border border-slate-200 bg-slate-50/70 p-2 text-xs">
                                            <summary class="cursor-pointer font-semibold text-purple-700 hover:text-purple-900 select-none">
                                                Transaction History ({{ $application->paymentTransactions->count() }})
                                            </summary>
                                            <div class="mt-2 space-y-2">
                                                @foreach ($application->paymentTransactions as $tx)
                                                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 text-xs space-y-1">
                                                        <div class="flex items-center justify-between">
                                                            <span class="font-bold text-slate-900">₱{{ number_format((float) $tx->amount, 2) }}</span>
                                                            <span class="rounded px-2 py-0.5 text-[10px] font-bold {{ $tx->status === 'verified' ? 'bg-emerald-100 text-emerald-800' : ($tx->status === 'pending_verification' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                                                {{ $tx->statusLabel() }}
                                                            </span>
                                                        </div>
                                                        <p class="text-[11px] text-slate-600">
                                                            @if ($tx->ticket_number)
                                                                Ticket #: <strong class="font-mono text-purple-900">{{ $tx->ticket_number }}</strong>
                                                                · {{ $tx->typeLabel() }} · Requested {{ $tx->created_at->format('M d, Y g:i A') }}
                                                            @else
                                                                OR #: <strong class="font-mono">{{ $tx->or_number ?: 'N/A' }}</strong> · {{ $tx->typeLabel() }} · {{ $tx->paid_at?->format('M d, Y') ?? 'N/A' }}
                                                            @endif
                                                        </p>
                                                        @if ($tx->receipt_proof_path)
                                                            <a href="{{ route('admin.payment-schedules.transactions.proof', $tx) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 hover:text-purple-900">
                                                                View uploaded receipt proof
                                                            </a>
                                                        @endif
                                                        @if ($tx->recordedByAdmin)
                                                            <p class="text-[10px] text-slate-400">Recorded by {{ $tx->recordedByAdmin->name }}</p>
                                                        @endif
                                                        @if ($tx->status === 'pending_verification')
                                                            <form method="POST" action="{{ route('admin.payment-schedules.transactions.verify', $tx) }}" class="mt-2 space-y-2 border-t border-slate-100 pt-2" data-single-action>
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    @if ($tx->isOnsiteTicket())
                                                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                                            <input name="or_number" required maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" class="form-field text-xs font-mono" placeholder="Cashier OR #" aria-label="Cashier official receipt number">
                                                                            <input name="paid_at" type="date" required max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" class="form-field text-xs" aria-label="Payment date">
                                                                        </div>
                                                                    @endif
                                                                    <div class="flex items-center gap-1.5">
                                                                        <button name="action" value="verify" class="rounded bg-emerald-600 px-2 py-1 text-[11px] font-bold text-white hover:bg-emerald-700">{{ $tx->isOnsiteTicket() ? 'Verify Ticket' : 'Verify OR' }}</button>
                                                                        <button name="action" value="reject" class="rounded bg-rose-600 px-2 py-1 text-[11px] font-bold text-white hover:bg-rose-700">Reject</button>
                                                                    </div>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-14 text-center">
                                    <p class="font-bold text-slate-950">No payment records found</p>
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

        <!-- Native HTML5 Dialog: Record On-Site Payment -->
        <dialog id="record-onsite-payment-dialog" data-dashboard-dialog class="m-auto max-h-[90vh] w-[min(94vw,36rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="record-dialog-title">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 id="record-dialog-title" class="font-display text-xl font-bold text-slate-950">Record On-Site Payment</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Issue an Official Receipt (OR) entry to credit a trainee's tuition balance.</p>
                </div>
                <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close dialog" title="Close">
                    <x-dashboard-icon name="xmark" class="h-4 w-4" />
                </button>
            </div>

            <form id="record-onsite-payment-form" method="POST" action="{{ route('admin.payment-schedules.transactions.store', $firstApp->id ?? 1) }}" class="space-y-4 p-6" data-dashboard-dialog-form data-submit-label="Recording payment...">
                @csrf

                <div>
                    <label for="record-enrollee-select" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Enrollee / Trainee</label>
                    <select id="record-enrollee-select" class="form-field" required>
                        @foreach ($allApplications as $app)
                            <option value="{{ $app->id }}" data-balance="{{ $app->remainingBalance() }}" @selected(($firstApp->id ?? null) === $app->id)>
                                {{ $app->last_name }}, {{ $app->first_name }} ({{ $app->email }}) — Balance: ₱{{ number_format($app->remainingBalance(), 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="record-or-number" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Official Receipt (OR) #</label>
                        <input id="record-or-number" name="or_number" required maxlength="100" class="form-field font-mono" placeholder="e.g. OR-2026-0891">
                    </div>
                    <div>
                        <label for="record-paid-at" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Payment Date</label>
                        <input id="record-paid-at" name="paid_at" type="date" required value="{{ now()->toDateString() }}" class="form-field">
                    </div>
                </div>

                <div>
                    <label for="record-amount" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Amount Paid (PHP)</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-bold text-slate-500">₱</span>
                        <input id="record-amount" name="amount" type="number" step="0.01" min="1" max="100000" required value="2000.00" class="form-field pl-8">
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <button type="button" data-preset-amount="2000.00" data-preset-type="downpayment" class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">₱2,000 Downpayment</button>
                        <button type="button" data-preset-amount="5000.00" data-preset-type="installment" class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">₱5,000 Installment</button>
                        <button type="button" id="record-btn-clear-balance" class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">Clear Balance</button>
                    </div>
                </div>

                <div>
                    <label for="record-tx-type" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Payment Classification</label>
                    <select id="record-tx-type" name="transaction_type" class="form-field" required>
                        <option value="downpayment">Downpayment (Initial ₱2,000)</option>
                        <option value="installment">Monthly Installment</option>
                        <option value="full_payment">Full Program Payment</option>
                        <option value="balance_settlement">Final Balance Settlement</option>
                    </select>
                </div>

                <div>
                    <label for="record-notes" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Admin Notes / Remarks (Optional)</label>
                    <textarea id="record-notes" name="notes" rows="2" maxlength="1000" class="form-field" placeholder="e.g. Received cash payment at registration desk..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                    <button type="submit" data-action-button class="primary-action">
                        <x-dashboard-icon name="check" class="h-4 w-4" />
                        <span>Save & Credit Payment</span>
                    </button>
                </div>
            </form>
        </dialog>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dialog = document.getElementById('record-onsite-payment-dialog');
            const form = document.getElementById('record-onsite-payment-form');
            const enrolleeSelect = document.getElementById('record-enrollee-select');
            const amountInput = document.getElementById('record-amount');
            const txTypeSelect = document.getElementById('record-tx-type');
            const clearBalanceBtn = document.getElementById('record-btn-clear-balance');
            const baseUrl = '{{ url('/admin/payment-scheduling') }}';

            const updateFormAction = (appId) => {
                if (!form || !appId) return;
                form.action = `${baseUrl}/${appId}/transactions`;
            };

            const updateBalancePreset = () => {
                const selectedOption = enrolleeSelect?.selectedOptions?.[0];
                if (!selectedOption) return;
                const balance = parseFloat(selectedOption.dataset.balance || '0');
                if (clearBalanceBtn) {
                    clearBalanceBtn.textContent = `Clear Balance (₱${balance.toLocaleString('en-US', { minimumFractionDigits: 2 })})`;
                    clearBalanceBtn.dataset.balanceAmount = balance.toFixed(2);
                }
            };

            if (enrolleeSelect) {
                enrolleeSelect.addEventListener('change', () => {
                    updateFormAction(enrolleeSelect.value);
                    updateBalancePreset();
                });
                updateFormAction(enrolleeSelect.value);
                updateBalancePreset();
            }

            document.querySelectorAll('[data-preset-amount]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (amountInput) amountInput.value = btn.dataset.presetAmount;
                    if (txTypeSelect && btn.dataset.presetType) txTypeSelect.value = btn.dataset.presetType;
                });
            });

            if (clearBalanceBtn) {
                clearBalanceBtn.addEventListener('click', () => {
                    const balance = clearBalanceBtn.dataset.balanceAmount || '0.00';
                    if (amountInput) amountInput.value = balance;
                    if (txTypeSelect) txTypeSelect.value = 'balance_settlement';
                });
            }

            // Per-row "Record Payment" button click handlers
            document.querySelectorAll('[data-record-for-app]').forEach((button) => {
                button.addEventListener('click', () => {
                    const appId = button.dataset.appId;
                    const balance = parseFloat(button.dataset.appBalance || '0');

                    if (enrolleeSelect && appId) {
                        enrolleeSelect.value = appId;
                        updateFormAction(appId);
                        updateBalancePreset();
                    }

                    if (amountInput) {
                        amountInput.value = balance > 0 && balance < 2000 ? balance.toFixed(2) : '2000.00';
                    }

                    if (txTypeSelect) {
                        txTypeSelect.value = balance <= 0 ? 'balance_settlement' : 'downpayment';
                    }

                    if (dialog && typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    }
                });
            });
        });
    </script>
@endsection
