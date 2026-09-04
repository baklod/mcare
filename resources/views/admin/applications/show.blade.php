@extends('admin.layouts.app', ['title' => 'Review application '.$admission->application_number.' | MCARE Admin'])

@section('content')
    @php
        $badgeClasses = [
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'denied' => 'bg-red-50 text-red-700 ring-red-100',
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.applications.index') }}" class="inline-flex w-fit items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">Back to applications</a>
        <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $badgeClasses[$admission->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">{{ $admission->statusLabel() }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-purple-700">{{ $admission->application_number }}</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $admission->fullName() }}</h2>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Email' => $admission->email,
                    'Contact number' => $admission->contact_number,
                    'Program' => $admission->program,
                    'Preferred schedule' => $admission->schedule_preference ?: 'Not specified',
                    'Educational attainment' => $admission->educational_attainment,
                    'Submitted' => $admission->created_at?->format('M d, Y g:i A'),
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
            @if (filled($admission->notes))
                <div class="mt-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Applicant note</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $admission->notes }}</p>
                </div>
            @endif
            @if ($admission->enrollment)
                <p class="mt-6 text-sm font-semibold text-purple-800">This number is already linked to a submitted enrollment.</p>
                <a href="{{ route('admin.enrollments.show', $admission->enrollment) }}" class="mt-2 inline-flex text-sm font-bold text-purple-700 hover:text-purple-900">Open enrollment record</a>
            @endif
        </section>

        <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Review decision</p>
            <form method="POST" action="{{ route('admin.applications.update', $admission) }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500" for="status">Decision</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" @disabled($admission->enrollment && $admission->isApproved())>
                        <option value="approved" @selected(old('status', $admission->status) === 'approved')>Approve for enrollment</option>
                        <option value="denied" @selected(old('status', $admission->status) === 'denied')>Deny</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500" for="admin_notes">Notes</label>
                    <textarea id="admin_notes" name="admin_notes" class="min-h-28 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Required when denying">{{ old('admin_notes', $admission->admin_notes) }}</textarea>
                </div>
                <button type="submit" class="primary-action w-full" @disabled($admission->enrollment && $admission->isApproved())>Save decision</button>
            </form>
            @if ($admission->reviewed_at)
                <p class="mt-4 text-xs text-slate-500">Last reviewed {{ $admission->reviewed_at->format('M d, Y g:i A') }}@if($admission->reviewer) by {{ $admission->reviewer->name }}@endif.</p>
            @endif
        </aside>
    </div>
@endsection
