@extends('admin.layouts.app', ['title' => 'Payment Scheduling | MCARE Admin'])

@section('content')
    @php
        $paymentBadgeClasses = [
            'not_selected' => 'bg-slate-50 text-slate-700 ring-slate-100',
            'onsite_pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'online_pending' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'expired' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $sampleSchedules = [
            ['label' => 'AM payment window', 'days' => $activeBatch?->am_days ?: 'MWF', 'time' => $activeBatch?->scheduleLabelFor('AM') ?: 'MWF | 8:00 AM - 12:00 PM', 'room' => $activeBatch?->am_room ?: 'Cashier / Admin Office'],
            ['label' => 'PM payment window', 'days' => $activeBatch?->pm_days ?: 'TTS', 'time' => $activeBatch?->scheduleLabelFor('PM') ?: 'TTS | 1:00 PM - 5:00 PM', 'room' => $activeBatch?->pm_room ?: 'Online checkout monitoring'],
        ];
    @endphp

    <section class="space-y-6">
        <div class="rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40 sm:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase text-purple-600">Payment scheduling</p>
                    <h1 class="mt-2 text-4xl font-bold leading-tight text-slate-900">Online and on-site queues</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">
                        Track pay-on-site receipts and PayMongo-ready online payments using the active batch schedule.
                    </p>
                </div>
                <a href="{{ route('admin.schedules.index') }}" class="inline-flex w-fit items-center justify-center rounded-full border border-purple-200 bg-white px-5 py-3 text-sm font-bold text-purple-700 hover:bg-purple-50">
                    Edit batch schedule
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            @foreach (['On-site receipts' => $stats['onsite'], 'Online intents' => $stats['online'], 'Verified paid' => $stats['paid'], 'Expired records' => $stats['expired']] as $label => $count)
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $count }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @foreach ($sampleSchedules as $sample)
                <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold uppercase text-purple-600">{{ $sample['label'] }}</p>
                            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $sample['days'] }}</h2>
                        </div>
                        <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 ring-1 ring-purple-100">Online + on-site</span>
                    </div>
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase text-slate-500">Class time</p>
                            <p class="mt-1 text-sm font-bold leading-6 text-slate-900">{{ $sample['time'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase text-slate-500">Room / desk</p>
                            <p class="mt-1 text-sm font-bold leading-6 text-slate-900">{{ $sample['room'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @foreach ([
                'On-site payment queue' => ['items' => $onsiteApplications, 'empty' => 'Applicants will appear here after choosing pay on site.'],
                'Online PayMongo queue' => ['items' => $onlineApplications, 'empty' => 'Applicants will appear here after choosing online payment.'],
            ] as $title => $queue)
                <section class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xl shadow-slate-200/60">
                    <div class="border-b border-slate-100 p-6">
                        <p class="text-sm font-bold uppercase text-purple-600">Queue</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $title }}</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($queue['items'] as $application)
                            <article class="grid grid-cols-1 gap-4 p-5 md:grid-cols-[1.1fr_1fr_auto]">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $application->last_name }}, {{ $application->first_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $application->email }}</p>
                                    <a href="{{ route('admin.enrollments.show', $application) }}" class="mt-2 inline-flex text-xs font-bold text-purple-700 hover:text-purple-900">Open review</a>
                                </div>
                                <div class="text-sm leading-6 text-slate-600">
                                    <p class="font-bold text-slate-900">{{ $application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned batch' }}</p>
                                    <p>{{ $application->batch?->scheduleLabelFor($application->schedule_preference) ?? $application->schedule_preference.' schedule pending' }}</p>
                                    <p class="text-xs text-slate-500">{{ $application->batch?->roomFor($application->schedule_preference) ?: 'Room TBA' }}</p>
                                    <p class="mt-2 break-all text-xs text-slate-500">{{ $application->payment_receipt_number ?: $application->paymongo_checkout_reference ?: $application->payment_reference ?: 'Reference pending' }}</p>
                                </div>
                                <div class="md:text-right">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                        {{ $application->paymentStatusLabel() }}
                                    </span>
                                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ $application->effectivePaymentDeadline()?->format('M d, Y g:i A') ?? 'Deadline TBA' }}</p>
                                </div>
                            </article>
                        @empty
                            <div class="px-5 py-12 text-center">
                                <p class="font-bold text-slate-900">No records yet</p>
                                <p class="mt-2 text-sm text-slate-500">{{ $queue['empty'] }}</p>
                            </div>
                        @endforelse
                    </div>
                    @if ($queue['items']->hasPages())
                        <div class="border-t border-slate-100 px-5 py-4">
                            {{ $queue['items']->links() }}
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </section>
@endsection
