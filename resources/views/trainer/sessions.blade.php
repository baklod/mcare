@extends('trainer.layouts.app', ['title' => 'Sessions | MCARE Trainer'])

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-stone-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Sessions</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Teaching schedule</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-600">The calendar updates from the active batch schedule maintained by the administrator. Select any date to see every class for that day together.</p>
        </div>
        @if ($activeBatch)
            <div class="rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm">
                <p class="font-bold text-violet-950">{{ $activeBatch->name }} {{ $activeBatch->year }}</p>
                <p class="mt-1 text-violet-800">Updated {{ $activeBatch->updated_at?->format('M d, Y g:i A') }} &middot; {{ $sessions->count() }} sessions</p>
            </div>
        @endif
    </header>

    @if ($activeBatch)
        <x-training-calendar
            :month="$month"
            :sessions="$sessions"
            :selected-date="$calendarSelectedDate"
            :month-route="route('trainer.sessions')"
            eyebrow="Live admin schedule"
            :heading="$month->format('F Y').' teaching calendar'"
            description="Select a date to open the complete teaching agenda. AM and PM sessions stay visible together—no popups or one-by-one cards."
            empty-message="No teaching sessions fall on this date."
        />
    @else
        <section class="dashboard-panel text-center">
            <x-dashboard-icon name="calendar-days" class="mx-auto h-9 w-9 text-stone-400" />
            <h2 class="mt-4 text-xl font-bold text-stone-950">No active training batch</h2>
            <p class="mt-2 text-sm text-stone-600">The calendar will appear after the administrator activates and schedules a batch.</p>
        </section>
    @endif
</div>
@endsection
