<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TrainerSearchService
{
    /**
     * @return list<array{key: string, label: string, results: list<array{title: string, subtitle: string, href: string}>}>
     */
    public function search(User $trainer, string $query, int $limit = 8): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $batch = TrainingBatch::assignedTo($trainer);
        $groups = [
            $this->group('pages', 'Pages', $this->pages($query)),
            $this->group('people', 'People', $this->people($batch, $query, $limit)),
            $this->group('modules', 'Modules', $this->modules($trainer, $query, $limit)),
            $this->group('quizzes', 'Quizzes', $this->quizzes($trainer, $query, $limit)),
            $this->group('classes', 'Classes', $this->classes($batch, $query)),
            $this->group('posts', 'Stream', $this->posts($trainer, $query, $limit)),
        ];

        return array_values(array_filter(
            $groups,
            fn (array $group): bool => $group['results'] !== [],
        ));
    }

    /**
     * @param  list<array{title: string, subtitle: string, href: string}>  $results
     * @return array{key: string, label: string, results: list<array{title: string, subtitle: string, href: string}>}
     */
    private function group(string $key, string $label, array $results): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'results' => $results,
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string, href: string}>
     */
    private function pages(string $query): array
    {
        $pages = [
            ['title' => 'Teaching Day', 'subtitle' => 'Today schedule and learner follow-up', 'href' => route('trainer.dashboard'), 'keywords' => 'dashboard home today teaching agenda'],
            ['title' => 'Stream', 'subtitle' => 'Class announcements and reminders', 'href' => route('trainer.stream'), 'keywords' => 'announcements posts news feed stream'],
            ['title' => 'Classwork', 'subtitle' => 'Learning modules and quizzes', 'href' => route('trainer.resources'), 'keywords' => 'modules resources quizzes assessments classwork'],
            ['title' => 'Calendar', 'subtitle' => 'Batch sessions and teaching schedule', 'href' => route('trainer.sessions'), 'keywords' => 'sessions schedule calendar'],
            ['title' => 'Classes', 'subtitle' => 'Assigned batch overview', 'href' => route('trainer.trainings'), 'keywords' => 'trainings batches classes'],
            ['title' => 'Attendance', 'subtitle' => 'Daily class attendance records', 'href' => route('trainer.attendance.index'), 'keywords' => 'attendance present absent roster'],
            ['title' => 'People', 'subtitle' => 'Approved learner roster', 'href' => route('trainer.trainees'), 'keywords' => 'trainees learners roster people students'],
            ['title' => 'Competency Records', 'subtitle' => 'TESDA grading board', 'href' => route('trainer.competencies.index'), 'keywords' => 'competency grades records tesda board'],
            ['title' => 'Certificates', 'subtitle' => 'Completion eligibility', 'href' => route('trainer.certificates'), 'keywords' => 'certificates cotc completion'],
            ['title' => 'Reports', 'subtitle' => 'Training delivery snapshot', 'href' => route('trainer.reports'), 'keywords' => 'reports stats snapshot'],
        ];

        $needle = mb_strtolower($query);

        return collect($pages)
            ->filter(function (array $page) use ($needle): bool {
                $haystack = mb_strtolower($page['title'].' '.$page['subtitle'].' '.$page['keywords']);

                return str_contains($haystack, $needle);
            })
            ->map(fn (array $page): array => [
                'title' => $page['title'],
                'subtitle' => $page['subtitle'],
                'href' => $page['href'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, href: string}>
     */
    private function people(?TrainingBatch $batch, string $query, int $limit): array
    {
        if (! $batch) {
            return [];
        }

        return EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('training_batch_id', $batch->id)
            ->tap(fn (Builder $builder) => $this->matchTokens($builder, [
                'first_name',
                'last_name',
                'email',
                'contact_number',
            ], $query))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(fn (EnrollmentApplication $trainee): array => [
                'title' => trim($trainee->last_name.', '.$trainee->first_name),
                'subtitle' => trim($trainee->email.' · '.$trainee->schedule_preference),
                'href' => route('trainer.competencies.edit', $trainee),
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, href: string}>
     */
    private function modules(User $trainer, string $query, int $limit): array
    {
        return TrainingModule::query()
            ->where('trainer_id', $trainer->id)
            ->tap(fn (Builder $builder) => $this->matchTokens($builder, [
                'title',
                'module_code',
                'topic',
                'description',
            ], $query))
            ->latest('published_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (TrainingModule $module): array => [
                'title' => $module->title,
                'subtitle' => trim(($module->module_code ? $module->module_code.' · ' : '').($module->topic ?: 'Classwork module')),
                'href' => route('trainer.modules.show', $module),
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, href: string}>
     */
    private function quizzes(User $trainer, string $query, int $limit): array
    {
        return Quiz::query()
            ->where('trainer_id', $trainer->id)
            ->tap(fn (Builder $builder) => $this->matchTokens($builder, [
                'title',
                'instructions',
            ], $query))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Quiz $quiz): array => [
                'title' => $quiz->title,
                'subtitle' => $quiz->is_published ? 'Published quiz' : 'Draft quiz',
                'href' => route('trainer.quizzes.edit', $quiz),
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, href: string}>
     */
    private function classes(?TrainingBatch $batch, string $query): array
    {
        if (! $batch) {
            return [];
        }

        $haystack = mb_strtolower(trim($batch->name.' '.$batch->year));
        if (! str_contains($haystack, mb_strtolower($query))) {
            return [];
        }

        return [[
            'title' => trim($batch->name.' '.$batch->year),
            'subtitle' => 'Assigned class',
            'href' => route('trainer.trainings'),
        ]];
    }

    /**
     * @return list<array{title: string, subtitle: string, href: string}>
     */
    private function posts(User $trainer, string $query, int $limit): array
    {
        return TrainerAnnouncement::query()
            ->where('trainer_id', $trainer->id)
            ->tap(fn (Builder $builder) => $this->matchTokens($builder, [
                'title',
                'message',
            ], $query))
            ->orderByDesc('posted_at')
            ->limit($limit)
            ->get()
            ->map(fn (TrainerAnnouncement $post): array => [
                'title' => $post->title,
                'subtitle' => Str::limit(trim(strip_tags((string) $post->message)), 80),
                'href' => route('trainer.stream'),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $columns
     */
    private function matchTokens(Builder $query, array $columns, string $term): void
    {
        $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $like = $this->likePattern($token);
            if ($like === '%%') {
                continue;
            }

            $query->where(function (Builder $inner) use ($columns, $like): void {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $inner->where($column, 'like', $like);
                    } else {
                        $inner->orWhere($column, 'like', $like);
                    }
                }
            });
        }
    }

    private function likePattern(string $token): string
    {
        return '%'.str_replace(['\\', '%', '_'], '', $token).'%';
    }
}
