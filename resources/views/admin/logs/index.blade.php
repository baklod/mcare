@extends('admin.layouts.app', ['title' => 'Admin Logs | MCARE Admin'])

@section('content')
    <section class="rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40 sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase text-purple-600">Security</p>
                <h1 class="mt-2 text-4xl font-bold leading-tight text-slate-900">Admin logs</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">
                    Track login activity, review decisions, document downloads, and schedule changes for audit and anti-abuse review.
                </p>
            </div>
            <form method="GET" action="{{ route('admin.logs.index') }}" class="flex w-full gap-2 lg:w-auto">
                <input name="search" type="search" value="{{ $search }}" placeholder="Search action, admin, IP" class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100 lg:w-72">
                <button class="rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Search</button>
            </form>
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
