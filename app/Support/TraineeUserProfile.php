<?php

namespace App\Support;

use App\Models\EnrollmentApplication;
use App\Models\User;

class TraineeUserProfile
{
    /**
     * Personal and contact fields copied from enrollment onto the user record.
     *
     * @return list<string>
     */
    public static function fields(): array
    {
        return [
            'first_name',
            'middle_name',
            'last_name',
            'extension_name',
            'contact_email',
            'contact_number',
            'birth_date',
            'birthplace_city',
            'birthplace_province',
            'birthplace_region',
            'gender',
            'civil_status',
            'employment_status',
            'employment_type',
            'nationality',
            'street',
            'barangay',
            'city',
            'province',
            'region',
            'zip_code',
            'educational_attainment',
            'school_name',
            'year_graduated',
            'guardian_name',
            'guardian_address',
        ];
    }

    /**
     * @param  array<string, mixed>|EnrollmentApplication  $source
     * @return array<string, mixed>
     */
    public static function attributesFrom(array|EnrollmentApplication $source): array
    {
        $data = $source instanceof EnrollmentApplication
            ? $source->only([...self::fields(), 'email'])
            : $source;

        $attributes = [];

        foreach (self::fields() as $field) {
            $attributes[$field] = $data[$field] ?? null;
        }

        $attributes['contact_email'] = $data['contact_email'] ?? $data['email'] ?? null;

        $name = trim(collect([
            $attributes['first_name'],
            $attributes['middle_name'],
            $attributes['last_name'],
            $attributes['extension_name'],
        ])->filter()->implode(' '));

        if ($name !== '') {
            $attributes['name'] = $name;
        }

        if ($source instanceof EnrollmentApplication) {
            $attributes['trainee_status'] = $source->status === EnrollmentApplication::STATUS_APPROVED
                ? ($source->learning_status ?: EnrollmentApplication::LEARNING_ACTIVE)
                : null;
        }

        return $attributes;
    }

    public static function sync(User $user, array|EnrollmentApplication $source): User
    {
        $user->forceFill(self::attributesFrom($source))->save();

        return $user->refresh();
    }
}
