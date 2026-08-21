@extends('admin.layouts.app', ['title' => 'Announcements & Reminders | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Communication & Notices</p>
        <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="dashboard-section-title text-3xl">Announcements & Reminders</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Post institutional updates, batch schedule announcements, or monthly tuition payment reminders. You can target all trainees, a specific batch, or an individual enrollee, with optional email delivery.
                </p>
            </div>
            <a href="{{ route('admin.payment-schedules.index') }}" class="secondary-action">
                <x-dashboard-icon name="credit-card" class="h-4 w-4" />
                <span>View Payment Ledger</span>
            </a>
        </div>
    </header>

    @if (session('saved'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" data-auto-dismiss="5000">
            {{ session('saved') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[440px_1fr]">
        <!-- Create Announcement Form -->
        <aside class="dashboard-panel space-y-5">
            <header class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-950">Publish Notice or Reminder</h2>
                <p class="mt-1 text-xs text-slate-500">Dispatches in-app notifications and optional email notices.</p>
            </header>

            <form method="POST" action="{{ route('admin.announcements.store') }}" class="space-y-4" id="admin-announcement-form">
                @csrf

                <div>
                    <label for="announcement-title" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Title / Subject</label>
                    <input id="announcement-title" name="title" required maxlength="200" value="{{ old('title') }}" class="form-field" placeholder="e.g. Monthly Tuition Due Date: September 2026">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="announcement-kind" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Category</label>
                        <select id="announcement-kind" name="kind" class="form-field" required>
                            @foreach ($kinds as $key => $label)
                                <option value="{{ $key }}" @selected(old('kind', 'reminder') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="announcement-due" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Due Date (Optional)</label>
                        <input id="announcement-due" name="due_date" type="date" value="{{ old('due_date') }}" class="form-field">
                    </div>
                </div>

                <div>
                    <label for="announcement-target" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Target Audience</label>
                    <select id="announcement-target" name="target_type" class="form-field" required>
                        @foreach ($targetTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('target_type', 'all') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="target-batch-wrap" class="space-y-1.5 {{ old('target_type') === 'batch' ? '' : 'hidden' }}">
                    <label for="announcement-batch" class="block text-xs font-bold uppercase text-slate-600">Select Training Batch</label>
                    <select id="announcement-batch" name="training_batch_id" class="form-field">
                        <option value="">-- Choose Batch --</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" @selected((int) old('training_batch_id') === $batch->id)>
                                {{ $batch->name }} ({{ $batch->year }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="target-user-wrap" class="space-y-1.5 {{ old('target_type') === 'user' ? '' : 'hidden' }}">
                    <label for="announcement-user" class="block text-xs font-bold uppercase text-slate-600">Select Specific Enrollee</label>
                    <select id="announcement-user" name="target_user_id" class="form-field">
                        <option value="">-- Choose Enrollee --</option>
                        @foreach ($approvedTrainees as $trainee)
                            <option value="{{ $trainee->id }}" @selected((int) old('target_user_id') === $trainee->id)>
                                {{ $trainee->name }} ({{ $trainee->email }}) - {{ $trainee->enrollmentApplication?->batch?->name ?? 'Unassigned' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="announcement-message" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Message Content</label>
                    <textarea id="announcement-message" name="message" rows="4" required maxlength="5000" class="form-field" placeholder="Provide instructions, installment details, or announcement notes...">{{ old('message') }}</textarea>
                </div>

                <div class="rounded-xl border border-sky-100 bg-sky-50/50 p-3.5">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="send_email" value="1" @checked(old('send_email', true)) class="mt-0.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-xs text-slate-700">
                            <strong class="font-bold text-slate-950">Send Email Notification</strong><br>
                            Dispatches a branded email notice to the selected audience with due dates and payment links.
                        </span>
                    </label>
                </div>

                <button type="submit" class="primary-action w-full justify-center">
                    <x-dashboard-icon name="paper-plane" class="h-4 w-4" />
                    <span>Publish & Send Notice</span>
                </button>
            </form>
        </aside>

        <!-- Published Announcements Table -->
        <section class="dashboard-table-wrap">
            <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-950">Notice History</h2>
                    <p class="text-xs text-slate-500">Recent announcements, reminders, and notifications sent by administrators.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="dashboard-table min-w-[50rem]">
                    <thead>
                        <tr>
                            <th>Notice</th>
                            <th>Audience</th>
                            <th>Category</th>
                            <th>Due Date</th>
                            <th>Delivery</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($announcements as $item)
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-950">{{ $item->title }}</p>
                                    <p class="mt-1 text-xs text-slate-600 line-clamp-2">{{ $item->message }}</p>
                                    <p class="mt-1.5 text-[11px] text-slate-400">
                                        By {{ $item->author?->name ?? 'Admin' }} · {{ $item->posted_at?->format('M d, Y g:i A') ?? $item->created_at->format('M d, Y') }}
                                    </p>
                                </td>
                                <td>
                                    @if ($item->target_type === 'all')
                                        <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-200">All Batches</span>
                                    @elseif ($item->target_type === 'batch')
                                        <span class="inline-flex rounded-md bg-purple-50 px-2.5 py-1 text-xs font-bold text-purple-700 ring-1 ring-inset ring-purple-200">
                                            Batch: {{ $item->batch?->name ?? 'Deleted Batch' }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            {{ $item->targetUser?->name ?? 'Individual Enrollee' }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ $kinds[$item->kind] ?? str($item->kind)->headline() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($item->due_date)
                                        <span class="font-bold text-rose-700 text-xs">{{ $item->due_date->format('M d, Y') }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->send_email)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                            <x-dashboard-icon name="envelope" class="h-3 w-3" />
                                            <span>Email + App</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                            <x-dashboard-icon name="bell" class="h-3 w-3" />
                                            <span>In-App Only</span>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.announcements.destroy', $item) }}" onsubmit="return confirm('Are you sure you want to delete this notice?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center text-xs font-semibold text-red-600 hover:text-red-800">
                                            <x-dashboard-icon name="trash" class="h-3.5 w-3.5 mr-1" />
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    <p class="font-bold text-slate-900">No announcements posted yet</p>
                                    <p class="mt-1 text-xs">Use the form on the left to send notices or monthly tuition reminders.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($announcements->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $announcements->links() }}
                </div>
            @endif
        </section>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const targetSelect = document.getElementById('announcement-target');
        const batchWrap = document.getElementById('target-batch-wrap');
        const userWrap = document.getElementById('target-user-wrap');

        const syncAudienceFields = () => {
            const val = targetSelect?.value;
            if (batchWrap) batchWrap.classList.toggle('hidden', val !== 'batch');
            if (userWrap) userWrap.classList.toggle('hidden', val !== 'user');
        };

        if (targetSelect) {
            targetSelect.addEventListener('change', syncAudienceFields);
            syncAudienceFields();
        }
    });
</script>
@endsection
