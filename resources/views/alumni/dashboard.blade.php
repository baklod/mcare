@extends(($isAdminPreview ?? false) ? 'admin.layouts.app' : 'alumni.layouts.app', ['title' => 'Alumni Job Board | MCARE'])

@section('content')
<section class="space-y-6">
    @if ($isAdminPreview ?? false)
        <div class="flex flex-col gap-3 rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-purple-900">Admin preview of the alumni experience</p>
            <a href="{{ route('admin.learning.alumni-jobs') }}" class="text-sm font-bold text-purple-800 hover:text-purple-950">Back to Job Board management</a>
        </div>
    @endif

    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">MCARE Alumni</p>
        <div class="mt-3 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div><h1 class="text-3xl font-black text-slate-950 sm:text-4xl">Alumni Job Board</h1><p class="mt-2 text-sm text-slate-600">Privacy-reviewed caregiving duties coordinated by MCARE.</p></div>
            <a href="{{ route('notifications.index') }}" class="secondary-action inline-flex items-center gap-3 self-start lg:self-auto"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-700"><x-dashboard-icon name="bell" class="h-4 w-4" /></span><span><span class="block text-xs font-bold uppercase text-slate-500">Unread updates</span><span class="block text-lg font-black text-slate-950">{{ $unreadNotifications }}</span></span></a>
        </div>
    </header>

    @unless ($isAdminPreview ?? false)
        <section class="dashboard-panel flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between" aria-labelledby="availability-title" data-availability-state="{{ $alumniProfile->is_available_for_duty ? 'available' : 'unavailable' }}">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $alumniProfile->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}"><x-dashboard-icon :name="$alumniProfile->is_available_for_duty ? 'circle-check' : 'circle-minus'" class="h-5 w-5" /></span>
                <div><p class="dashboard-section-kicker">Caregiver availability</p><h2 id="availability-title" class="mt-1 text-xl font-black text-slate-950">{{ $alumniProfile->is_available_for_duty ? 'Available for Duty' : 'Currently unavailable' }}</h2><p class="mt-1 text-sm text-slate-500">{{ $alumniProfile->availability_updated_at ? 'Updated '.$alumniProfile->availability_updated_at->diffForHumans() : 'Set your current duty status.' }}</p></div>
            </div>
            <form method="POST" action="{{ route('alumni.availability.update') }}">
                @csrf @method('PATCH')
                <input type="hidden" name="is_available_for_duty" value="{{ $alumniProfile->is_available_for_duty ? '0' : '1' }}">
                <button type="submit" data-action-button class="{{ $alumniProfile->is_available_for_duty ? 'secondary-action' : 'primary-action' }} whitespace-nowrap">{{ $alumniProfile->is_available_for_duty ? 'Mark unavailable' : 'Mark Available for Duty' }}</button>
            </form>
        </section>
    @endunless

    <section aria-labelledby="career-board-title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="dashboard-section-kicker">Open duties</p><h2 id="career-board-title" class="mt-1 text-2xl font-black text-slate-950">Caregiving opportunities</h2></div><span class="text-sm font-semibold text-slate-500">{{ $jobs->total() }} available</span></div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @forelse ($jobs as $job)
                <article class="dashboard-panel flex h-full flex-col p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-700"><x-dashboard-icon name="briefcase" class="h-5 w-5" /></span><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Open duty</span></div>
                    <h3 class="mt-5 text-xl font-black text-slate-950">Estimated start {{ $job->estimated_start_date->format('M d, Y') }}</h3>
                    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-xs font-bold uppercase text-slate-400">Patient gender</dt><dd class="mt-1 font-bold text-slate-900">{{ $job->patientGenderLabel() }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase text-slate-400">Mobility</dt><dd class="mt-1 font-bold text-slate-900">{{ $job->mobilityStatusLabel() }}</dd></div>
                        @if ($job->patient_age !== null)<div><dt class="text-xs font-bold uppercase text-slate-400">Age</dt><dd class="mt-1 font-bold text-slate-900">{{ $job->patient_age }}</dd></div>@endif
                        @if ($job->specific_contraptions)<div><dt class="text-xs font-bold uppercase text-slate-400">Contraptions</dt><dd class="mt-1 font-bold text-slate-900">{{ $job->specific_contraptions }}</dd></div>@endif
                    </dl>
                    @if ($job->condition_summary)<p class="mt-5 flex-1 border-t border-slate-100 pt-4 text-sm leading-6 text-slate-600"><span class="font-bold text-slate-900">Care context:</span> {{ $job->condition_summary }}</p>@endif
                    <p class="mt-5 border-t border-slate-100 pt-4 text-sm font-semibold text-purple-700">Contact MCARE to confirm your interest in this duty.</p>
                </article>
            @empty
                <div class="dashboard-panel py-16 text-center lg:col-span-2"><x-dashboard-icon name="briefcase" class="mx-auto h-9 w-9 text-slate-300" /><h3 class="mt-4 text-lg font-bold text-slate-900">No open duties yet</h3><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">MCARE will publish privacy-reviewed caregiving duties here.</p></div>
            @endforelse
        </div>

        @if ($jobs->hasPages())<div class="mt-6">{{ $jobs->links() }}</div>@endif
    </section>
</section>
@endsection
