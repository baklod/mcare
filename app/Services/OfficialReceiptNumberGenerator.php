<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use Illuminate\Support\Str;

class OfficialReceiptNumberGenerator
{
    public function generate(): string
    {
        do {
            $number = 'MCARE-OR-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (
            PaymentTransaction::query()->where('or_number', $number)->exists()
            || EnrollmentApplication::query()->where('payment_receipt_number', $number)->exists()
        );

        return $number;
    }
}
