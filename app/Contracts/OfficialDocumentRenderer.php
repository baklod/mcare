<?php

namespace App\Contracts;

use App\Models\OfficialDocument;

interface OfficialDocumentRenderer
{
    public function render(OfficialDocument $document): string;
}
