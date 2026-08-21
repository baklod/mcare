<?php

namespace App\Rules;

use App\Support\TrainingModuleFiles;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class TrainingModuleFileType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The learning material could not be uploaded. Check the file size and try again.');

            return;
        }

        if ($message = TrainingModuleFiles::validationError($value)) {
            $fail($message);
        }
    }
}
