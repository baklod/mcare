@extends('trainee.layouts.app', ['title' => 'Payments | MCARE Trainee'])

@section('content')
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6"><p class="dashboard-section-kicker">Billing and payments</p><h1 class="dashboard-section-title mt-2 text-3xl">Payment summary</h1></header>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_360px]">
        <div class="dashboard-panel grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Method</p><p class="mt-2 text-lg font-black text-slate-900">{{ $application->payment_method ? str($application->payment_method)->headline() : 'Not selected' }}</p></div>
            <div class="rounded-xl bg-slate-50 p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Status</p><p class="mt-2 text-lg font-black text-slate-900">{{ $application->paymentStatusLabel() }}</p></div>
            <div class="rounded-xl bg-slate-50 p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</p><p class="mt-2 text-lg font-black text-slate-900">{{ $application->payment_currency }} {{ number_format((float) $application->payment_amount, 2) }}</p></div>
            <div class="rounded-xl bg-slate-50 p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</p><p class="mt-2 break-all text-lg font-black text-slate-900">{{ $application->payment_receipt_number ?: $application->paymongo_checkout_reference ?: $application->payment_reference ?: 'Reference pending' }}</p></div>
        </div>
        <aside class="dashboard-panel"><p class="text-sm leading-6 text-slate-600">Open the secure payment page to review or complete the action currently available for your record.</p><a href="{{ route('payment.show') }}" class="primary-action mt-5 w-full">Open payment page</a></aside>
    </div>
</section>
@endsection
