@extends('trainer.layouts.app', ['title' => 'Trainees | MCARE Trainer'])

@section('content')
<div class="mx-auto max-w-7xl space-y-7">
    <header class="border-b border-stone-200 pb-6"><p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">Trainees</p><h1 class="mt-2 text-3xl font-bold text-stone-950">Approved learner roster</h1><p class="mt-2 text-stone-600">Only admin-approved trainees appear here.</p></header>
    <form method="GET" action="{{ route('trainer.trainees') }}" class="flex gap-3 border border-stone-200 bg-white p-4"><input name="search" value="{{ $search }}" class="min-w-0 flex-1 border border-stone-300 px-4 py-2.5" placeholder="Search name or email"><button class="bg-violet-700 px-5 font-bold text-white">Search</button></form>
    <div class="overflow-x-auto border border-stone-200 bg-white">
        <table class="w-full min-w-[56rem] text-left text-sm"><thead class="bg-stone-50 text-xs uppercase text-stone-500"><tr><th class="px-5 py-4">Trainee</th><th class="px-5 py-4">Batch</th><th class="px-5 py-4">Schedule</th><th class="px-5 py-4">Module progress</th><th class="px-5 py-4">Payment</th><th class="px-5 py-4">Training state</th></tr></thead><tbody class="divide-y divide-stone-200">
        @forelse($trainees as $trainee)<tr><td class="px-5 py-4"><p class="font-bold text-stone-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p><p class="text-stone-500">{{ $trainee->email }}</p></td><td class="px-5 py-4">{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned' }}</td><td class="px-5 py-4">{{ $trainee->schedule_preference }}</td><td class="px-5 py-4"><span class="font-bold text-emerald-700">{{ $trainee->moduleProgress->where('status', 'completed')->count() }} complete</span><p class="text-xs text-stone-500">{{ $trainee->moduleProgress->where('status', 'in_progress')->count() }} in progress</p></td><td class="px-5 py-4">{{ $trainee->paymentStatusLabel() }}</td><td class="px-5 py-4">{{ $trainee->batch?->trainingStateLabel() ?? 'No batch' }}</td></tr>@empty<tr><td colspan="6" class="px-5 py-10 text-center text-stone-600">No approved trainees found.</td></tr>@endforelse
        </tbody></table>
    </div>
    {{ $trainees->links() }}
</div>
@endsection
