<?php

namespace App\Services;

class TorGradeScale
{
    /**
     * Convert a passing percentage to the official mark shown in the client TOR.
     * Scores below 75 remain blank because a TOR is issued only after competency.
     */
    public function fromPercentage(?float $percentage): ?float
    {
        if ($percentage === null || $percentage < 75) {
            return null;
        }

        $scale = [
            99 => 1.00,
            98 => 1.10,
            97 => 1.20,
            96 => 1.25,
            95 => 1.30,
            94 => 1.40,
            93 => 1.50,
            92 => 1.60,
            91 => 1.70,
            90 => 1.75,
            89 => 1.80,
            88 => 1.90,
            87 => 2.00,
            86 => 2.10,
            85 => 2.20,
            84 => 2.25,
            83 => 2.30,
            82 => 2.40,
            81 => 2.50,
            80 => 2.60,
            79 => 2.70,
            78 => 2.75,
            77 => 2.80,
            76 => 2.90,
            75 => 3.00,
        ];

        $wholeScore = min(99, (int) floor($percentage));

        return $scale[$wholeScore] ?? 1.00;
    }
}
