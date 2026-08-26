<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPE_FILE_UPLOAD = 'file_upload';

    public const TYPE_ENUMERATION = 'enumeration';

    protected $fillable = [
        'quiz_id',
        'type',
        'prompt',
        'options',
        'correct_option',
        'points',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option' => 'integer',
            'points' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /** @return list<string> */
    public static function types(): array
    {
        return [
            self::TYPE_MULTIPLE_CHOICE,
            self::TYPE_TRUE_FALSE,
            self::TYPE_FILE_UPLOAD,
            self::TYPE_ENUMERATION,
        ];
    }

    public function isFileUpload(): bool
    {
        return $this->type === self::TYPE_FILE_UPLOAD;
    }

    public function isEnumeration(): bool
    {
        return $this->type === self::TYPE_ENUMERATION;
    }

    public function requiresOptions(): bool
    {
        return in_array($this->type, [self::TYPE_MULTIPLE_CHOICE, self::TYPE_TRUE_FALSE], true);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function normalizedOptionIndex(mixed $value): ?int
    {
        if (is_int($value)) {
            $index = $value;
        } elseif (is_string($value) && ctype_digit($value)) {
            $index = (int) $value;
        } else {
            return null;
        }

        return array_key_exists($index, $this->options ?? []) ? $index : null;
    }

    public function isCorrectOption(mixed $value): bool
    {
        if ($this->isFileUpload() || $this->isEnumeration()) {
            return filled($value);
        }

        $index = $this->normalizedOptionIndex($value);

        return $index !== null && $index === $this->correct_option;
    }
}
