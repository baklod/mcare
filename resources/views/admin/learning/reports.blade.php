@extends('admin.layouts.app', ['title' => 'Learning Reports | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6"><p class="dashboard-section-kicker">Learning system · Reports</p><h1 class="mt-2 dashboard-section-title text-3xl">Batch operations report</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Compare enrollment, AM/PM distribution, approvals, verified payments, and trainer-published modules without mixing operational queues.</p></header>
    <div class="dashboard-table-wrap overflow-x-auto"><table class="dashboard-table min-w-[64rem]"><thead><tr><th>Batch</th><th>Enrollment</th><th>Training</th><th>Applications</th><th>AM</th><th>PM</th><th>Approved</th><th>Paid</th><th>Modules</th></tr></thead><tbody>
    @forelse($batches as $batch)<tr><td><p class="font-bold text-slate-950">{{ $batch->name }} {{ $batch->year }}</p><p class="mt-1 text-xs">{{ $batch->is_continuous_enrollment ? 'Continuous enrollment' : 'Deadline '.$batch->enrollment_ends_at?->format('M d, Y') }}</p></td><td><span class="dashboard-pill {{ $batch->acceptsEnrollment() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $batch->enrollmentStateLabel() }}</span></td><td>{{ $batch->trainingStateLabel() }}</td><td class="font-bold">{{ $batch->applications_count }}</td><td>{{ $batch->am_count }}</td><td>{{ $batch->pm_count }}</td><td>{{ $batch->approved_count }}</td><td>{{ $batch->paid_count }}</td><td>{{ $batch->modules_count }}</td></tr>
    @empty<tr><td colspan="9" class="py-14 text-center">Create a batch to begin reporting.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
