<?php

namespace Tests\Unit;

use App\Models\TrainingModule;
use App\Services\TraineeClassworkSequence;
use Tests\TestCase;

class TraineeClassworkSequenceTest extends TestCase
{
    public function test_module_codes_sort_by_numeric_code_order(): void
    {
        $sequence = new TraineeClassworkSequence;
        $later = new TrainingModule(['module_code' => 'HCS323302']);
        $later->id = 1;
        $earlier = new TrainingModule(['module_code' => 'HCS323301']);
        $earlier->id = 2;
        $uncoded = new TrainingModule(['module_code' => null]);
        $uncoded->id = 3;

        $sorted = $sequence->sort(collect([$later, $uncoded, $earlier]));

        $this->assertSame(['HCS323301', 'HCS323302', null], $sorted->pluck('module_code')->all());
    }
}
