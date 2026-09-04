<?php

namespace App\Console\Commands;

use App\Services\CareerGraduateNotifier;
use Illuminate\Console\Command;

class SendScheduledCareerSms extends Command
{
    protected $signature = 'career:send-scheduled-sms';

    protected $description = 'Send or retry Career Hub SMS to graduates with contact numbers.';

    public function handle(CareerGraduateNotifier $notifier): int
    {
        $due = $notifier->dueOpportunities();

        foreach ($due as $opportunity) {
            $notifier->sendDueSms($opportunity);
        }

        $this->info($due->count().' career SMS '.str('batch')->plural($due->count()).' processed.');

        return self::SUCCESS;
    }
}
