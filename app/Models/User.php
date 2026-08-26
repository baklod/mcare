<?php

namespace App\Models;

use App\Notifications\QueuedVerifyEmail;
use App\Support\RolePermissionMatrix;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmail, Notifiable;

    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if (! $user->wasRecentlyCreated && ! $user->wasChanged('role')) {
                return;
            }

            $tables = config('permission.table_names');

            // Fresh installs may create users before package tables are ready.
            if (! Schema::hasTable($tables['roles'] ?? 'roles')
                || ! Schema::hasTable($tables['model_has_roles'] ?? 'model_has_roles')) {
                return;
            }

            RolePermissionMatrix::syncUser($user);
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar_url',
        'role',
        'applicant_status',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function trainingModules(): HasMany
    {
        return $this->hasMany(TrainingModule::class, 'trainer_id');
    }

    public function trainingBatches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class, 'trainer_id');
    }

    public function trainerAnnouncements(): HasMany
    {
        return $this->hasMany(TrainerAnnouncement::class, 'trainer_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'trainer_id');
    }

    public function alumniProfile(): HasOne
    {
        return $this->hasOne(AlumniProfile::class);
    }

    public function historicalAlumniClaim(): HasOne
    {
        return $this->hasOne(HistoricalAlumniClaim::class);
    }

    public function enrollmentApplication(): HasOne
    {
        return $this->hasOne(EnrollmentApplication::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function adminAnnouncements(): HasMany
    {
        return $this->hasMany(AdminAnnouncement::class, 'author_id');
    }

    public function classroomComments(): HasMany
    {
        return $this->hasMany(ClassroomComment::class, 'author_id');
    }

    public function privateClassroomComments(): HasMany
    {
        return $this->hasMany(ClassroomComment::class, 'recipient_user_id');
    }

    public function isGraduate(): bool
    {
        $application = $this->relationLoaded('enrollmentApplication')
            ? $this->enrollmentApplication
            : $this->enrollmentApplication()->first();

        return $application?->status === EnrollmentApplication::STATUS_APPROVED
            && $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED;
    }
}
