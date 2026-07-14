@extends('admin.layouts.app', ['title' => 'Admin Logs | MCARE Admin'])

@section('content')
    <section class="rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40 sm:p-8">
        <div class="flex flex-col gap-6">
            <div>
                <p class="text-sm font-bold uppercase text-purple-600">Security</p>
                <h1 class="mt-2 text-4xl font-bold leading-tight text-slate-900">Admin logs</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">
                    Track login activity, review decisions, document downloads, and schedule changes for audit and anti-abuse review.
                </p>
            </div>
            <form method="GET" action="{{ route('admin.logs.index') }}" class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-[10rem_11rem_minmax(14rem,1fr)_auto]">
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Coverage
                    <select name="period" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-purple-400 focus:ring-4 focus:ring-purple-100">
                        @foreach (['all' => 'All activity', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                            <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Reference date
                    <input name="date" type="date" value="{{ $anchorDate->format('Y-m-d') }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-purple-400 focus:ring-4 focus:ring-purple-100">
                </label>
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500 sm:col-span-2 lg:col-span-1">
                    Search
                    <input name="search" type="search" value="{{ $search }}" placeholder="Action, account, or IP" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-400 focus:ring-4 focus:ring-purple-100">
                </label>
                <button class="self-end rounded-xl bg-purple-700 px-5 py-3 text-sm font-bold text-white hover:bg-purple-800">Apply</button>
            </form>
        </div>
        <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-slate-600">Showing <span class="font-black text-slate-900">{{ $rangeLabel }}</span></p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.logs.print', request()->query()) }}" target="_blank" rel="noopener" class="secondary-action">
                    <x-dashboard-icon name="print" class="mr-2" />Print report
                </a>
                <a href="{{ route('admin.logs.export', request()->query()) }}" class="primary-action">
                    <x-dashboard-icon name="file-excel" class="mr-2" />Export for Excel
                </a>
            </div>
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xl shadow-slate-200/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Time</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Admin</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IP</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-purple-50/40">
                            <td class="px-6 py-5 text-sm font-semibold text-slate-700">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-900">{{ $log->user?->name ?? 'System / unknown' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $log->user?->email ?? 'No account attached' }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 ring-1 ring-purple-100">{{ $log->action }}</span>
                            </td>
                            <td class="px-6 py-5 text-sm text-slate-600">{{ $log->ip_address ?? 'Unknown' }}</td>
                            <td class="px-6 py-5 text-sm text-slate-600">
                                @if ($log->meta)
                                    <pre class="max-w-lg whitespace-pre-wrap rounded-2xl bg-slate-50 p-3 text-xs leading-5 text-slate-600">{{ json_encode($log->meta, JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    No extra details
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <p class="text-lg font-bold text-slate-900">No admin logs yet</p>
                                <p class="mt-2 text-sm text-slate-500">Security events will appear here as admins use the system.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </section>
@endsection
