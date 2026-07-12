@extends('trainer.layouts.app', ['title' => 'Sessions | MCARE Trainer'])

@section('content')
<div class="mx-auto max-w-7xl space-y-7">
    <header class="flex flex-col gap-5 border-b border-stone-200 pb-6 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">Sessions</p><h1 class="mt-2 text-3xl font-bold text-stone-950">{{ $month->format('F Y') }} teaching calendar</h1><p class="mt-2 text-stone-600">Live from the active schedule saved by the admin.</p></div><form method="GET"><label class="text-xs font-bold uppercase text-stone-500">Month</label><div class="mt-2 flex gap-2"><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="border border-stone-300 px-3 py-2"><button class="bg-violet-700 px-4 font-bold text-white">View</button></div></form></header>
    @if($activeBatch)
        <section class="border border-violet-200 bg-violet-50 p-5"><p class="font-bold text-violet-950">{{ $activeBatch->name }} {{ $activeBatch->year }}</p><p class="mt-1 text-sm text-violet-800">Last schedule update: {{ $activeBatch->updated_at?->format('M d, Y g:i A') }} · {{ $sessions->count() }} sessions this month</p></section>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($sessionsByDate as $date => $daySessions)
                <article class="border border-stone-200 bg-white p-5"><p class="text-sm font-bold uppercase tracking-wide text-violet-700">{{ $daySessions->first()['date']->format('D, M j') }}</p><div class="mt-4 space-y-3">@foreach($daySessions as $session)<div class="border-l-4 border-violet-600 bg-stone-50 p-4"><p class="font-bold text-stone-950">{{ $session['period'] }} · {{ $session['time_range'] }}</p><p class="mt-1 text-sm text-stone-600">{{ $session['room'] }}</p></div>@endforeach</div></article>
            @empty
                <p class="border border-stone-200 bg-white p-6 text-stone-600">No sessions fall within this month and the admin-set training window.</p>
            @endforelse
        </div>
    @else
        <p class="border border-stone-200 bg-white p-6 text-stone-600">The admin has not activated a training batch yet.</p>
    @endif
</div>
@endsection
