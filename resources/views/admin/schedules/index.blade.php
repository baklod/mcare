@extends('admin.layouts.app', ['title' => 'Schedules | MCARE Admin'])

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
            <p class="text-sm text-slate-600">Review AM and PM class sessions across configured batches.</p>
            <a href="{{ route('admin.batches.index') }}" class="inline-flex w-fit items-center justify-center gap-2 rounded-xl border border-purple-200 bg-white px-5 py-2.5 text-sm font-semibold text-purple-700 transition hover:bg-purple-50">
                Manage batches
            </a>
        </header>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.schedules.index') }}" data-auto-filter class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(16rem,1fr)_auto] md:items-end">
                <input type="hidden" name="month" value="{{ $calendarMonth->format('Y-m') }}">
                @if ($calendarSelectedDate)
                    <input type="hidden" name="date" value="{{ $calendarSelectedDate }}">
                @endif
                <div>
                    <label for="schedule-batch-id" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Batch</label>
                    <select id="schedule-batch-id" name="batch_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        <option value="">All batches</option>
                        @foreach ($calendarBatches as $batch)
                            <option value="{{ $batch->id }}" @selected((int) $selectedBatchId === (int) $batch->id)>
                                {{ trim($batch->name.' '.$batch->year) }}
                                @if ($batch->am_days || $batch->pm_days)
                                    · AM {{ $batch->am_days ?: '—' }} · PM {{ $batch->pm_days ?: '—' }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="text-sm text-slate-500 md:pb-2">
                    @if ($selectedBatch)
                        Showing {{ trim($selectedBatch->name.' '.$selectedBatch->year) }} only.
                    @else
                        Showing every batch with a training window in this month.
                    @endif
                </p>
            </form>
        </div>

        <x-training-calendar
            :month="$calendarMonth"
            :sessions="$calendarSessions"
            :selected-date="$calendarSelectedDate"
            :month-route="url()->current()"
            :editable="true"
            eyebrow="Admin master calendar"
            :heading="$calendarMonth->format('F Y').' schedule overview'"
            :description="$calendarDescription"
            empty-message="No batch sessions are scheduled for this date."
        />
    </div>
@endsection
