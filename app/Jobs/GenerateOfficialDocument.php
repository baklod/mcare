<?php

namespace App\Jobs;

use App\Models\OfficialDocument;
use App\Services\OfficialDocumentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateOfficialDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public readonly int $documentId) {}

    public function handle(OfficialDocumentManager $manager): void
    {
        $manager->generateNow(OfficialDocument::query()->findOrFail($this->documentId));
    }
}
