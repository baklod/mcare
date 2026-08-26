@extends('trainer.layouts.app', ['title' => 'Trainees | MCARE Trainer'])

@section('content')
<div class="mx-auto max-w-7xl space-y-7">
    <header class="border-b border-stone-200 pb-6">
        <p class="dashboard-section-kicker">Trainees</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Approved learner roster</h1>
        <p class="mt-2 text-stone-600">Your roster is limited to the current batch assigned by the administrator. Review tuition payment status, downpayment clearance, LMS progress, and export summaries.</p>
    </header>

    <form method="GET" action="{{ route('trainer.trainees') }}" class="dashboard-panel grid gap-4 md:grid-cols-4">
        <div class="md:col-span-2">
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search</label>
            <input name="search" value="{{ $search }}" class="form-field" placeholder="Search name or email">
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Assigned batch</label>
            <select name="batch_id" class="form-field">
                <option value="">Current assignment</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" @selected($batchId === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label>
            <select name="schedule" class="form-field">
                <option value="">AM and PM</option>
                <option value="AM" @selected($schedule === 'AM')>AM</option>
                <option value="PM" @selected($schedule === 'PM')>PM</option>
            </select>
        </div>
        <div class="flex flex-wrap gap-2 md:col-span-4">
            <button class="primary-action">Apply filters</button>
            <a href="{{ route('trainer.trainees') }}" class="secondary-action">Reset</a>
            <a href="{{ route('trainer.trainees.export', request()->query()) }}" class="secondary-action">Export trainee summary</a>
        </div>
    </form>

    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table min-w-[62rem]">
            <thead>
                <tr>
                    <th>Trainee</th>
                    <th>Batch / Schedule</th>
                    <th>Module progress</th>
                    <th>Tuition & Payment</th>
                    <th>Training state</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trainees as $trainee)
                    @php
                        $totalFee = (float) ($trainee->total_program_fee ?? 22000.00);
                        $totalPaid = (float) ($trainee->total_paid_amount ?? 0.00);
                        $balance = $trainee->remainingBalance();
                        $isDownpaymentSatisfied = $trainee->isDownpaymentSatisfied();
                        $transactions = $trainee->paymentTransactions;
                    @endphp
                    <tr class="align-top">
                        <td>
                            <div class="flex items-center gap-3">
                                <x-user-avatar
                                    :user="$trainee->user"
                                    :name="trim($trainee->first_name.' '.$trainee->last_name)"
                                    class="grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800"
                                />
                                <div class="min-w-0">
                                    <p class="font-bold text-stone-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p>
                                    <p class="truncate text-xs text-stone-500">{{ $trainee->email }}</p>
                                    <x-graduate-batch-badge :application="$trainee" class="mt-1.5" />
                                    <p class="mt-1 text-[11px] text-stone-400">Tel: {{ $trainee->contact_number }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="font-bold text-stone-900">{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned' }}</p>
                            <p class="mt-1 text-xs text-stone-600">{{ $trainee->schedule_preference }} · {{ $trainee->batch?->scheduleLabelFor($trainee->schedule_preference) ?? 'Schedule pending' }}</p>
                        </td>
                        <td>
                            <span class="font-bold text-emerald-700">{{ $trainee->moduleProgress->where('status', 'completed')->count() }} complete</span>
                            <p class="text-xs text-amber-700">{{ $trainee->moduleProgress->where('status', 'awaiting_evaluation')->count() }} awaiting evaluation</p>
                            <p class="text-xs text-stone-500">{{ $trainee->moduleProgress->whereIn('status', ['not_started', 'in_progress', 'needs_remediation'])->count() }} active or for remediation</p>
                        </td>
                        <td>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold {{ $trainee->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' : ($isDownpaymentSatisfied ? 'bg-purple-50 text-purple-800 ring-1 ring-purple-200' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200') }}">
                                        {{ $trainee->paymentStatusLabel() }}
                                    </span>
                                </div>
                                <div class="text-[11px] space-y-0.5 text-slate-600">
                                    <p>Paid: <strong class="font-semibold text-emerald-700">₱{{ number_format($totalPaid, 2) }}</strong> / ₱{{ number_format($totalFee, 2) }}</p>
                                    @if($balance > 0)
                                        <p class="text-amber-700">Balance: <strong>₱{{ number_format($balance, 2) }}</strong></p>
                                    @endif
                                </div>

                                @if($transactions->isNotEmpty())
                                    <details class="mt-2 rounded-lg border border-stone-200 bg-stone-50 p-2 text-[11px]">
                                        <summary class="cursor-pointer font-bold text-purple-700 hover:text-purple-900 select-none">
                                            Payment Records ({{ $transactions->count() }})
                                        </summary>
                                        <div class="mt-2 space-y-1.5">
                                            @foreach($transactions as $tx)
                                                <div class="rounded border border-stone-200 bg-white p-2 text-[11px] space-y-0.5">
                                                    <div class="flex items-center justify-between font-bold">
                                                        <span>₱{{ number_format((float)$tx->amount, 2) }}</span>
                                                        <span class="{{ $tx->status === 'verified' ? 'text-emerald-700' : 'text-amber-700' }}">
                                                            {{ $tx->statusLabel() }}
                                                        </span>
                                                    </div>
                                                    <p class="text-stone-500">
                                                        @if($tx->or_number)
                                                            OR #: <strong class="font-mono text-stone-800">{{ $tx->or_number }}</strong>
                                                        @elseif($tx->ticket_number)
                                                            Ticket #: <strong class="font-mono text-purple-800">{{ $tx->ticket_number }}</strong>
                                                        @endif
                                                        · {{ $tx->typeLabel() }}
                                                    </p>
                                                    <p class="text-[10px] text-stone-400">{{ $tx->paid_at?->format('M d, Y') ?? $tx->created_at->format('M d, Y') }} · {{ str($tx->payment_channel)->headline() }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        </td>
                        <td>
                            <p>{{ $trainee->batch?->trainingStateLabel() ?? 'No batch' }}</p>
                            <x-graduate-batch-badge :application="$trainee" class="mt-2" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-stone-600">No approved trainees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $trainees->links() }}
</div>
@endsection
