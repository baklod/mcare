<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use Illuminate\View\View;

class PaymentScheduleController extends Controller
{
    public function index(): View
    {
        return view('admin.payment-schedules.index', [
            'activeBatch' => TrainingBatch::active(),
            'onsiteApplications' => EnrollmentApplication::query()
                ->with(['batch', 'user'])
                ->where('payment_method', 'onsite')
                ->where('payment_status', '!=', EnrollmentApplication::PAYMENT_NOT_SELECTED)
                ->latest('payment_selected_at')
                ->paginate(8, ['*'], 'onsite_page'),
            'onlineApplications' => EnrollmentApplication::query()
                ->with(['batch', 'user'])
                ->where('payment_method', 'online')
                ->where('payment_status', '!=', EnrollmentApplication::PAYMENT_NOT_SELECTED)
                ->latest('payment_selected_at')
                ->paginate(8, ['*'], 'online_page'),
            'stats' => [
                'onsite' => EnrollmentApplication::query()->where('payment_method', 'onsite')->count(),
                'online' => EnrollmentApplication::query()->where('payment_method', 'online')->count(),
                'paid' => EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->count(),
                'expired' => EnrollmentApplication::query()->where('payment_status', EnrollmentApplication::PAYMENT_EXPIRED)->count(),
            ],
        ]);
    }
}
