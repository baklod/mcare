<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\PublicSiteSetting;
use App\Models\PublicUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicUpdateController extends Controller
{
    public function index(): View
    {
        return $this->updateView();
    }

    public function edit(PublicUpdate $publicUpdate): View
    {
        return $this->updateView($publicUpdate);
    }

    public function store(Request $request): RedirectResponse
    {
        $update = PublicUpdate::query()->create($this->validated($request));

        AdminActivityLog::record($request->user(), 'public_update.created', $update, [
            'title' => $update->title,
            'facebook_url' => $update->facebook_url,
            'is_published' => $update->is_published,
        ]);

        return redirect()
            ->route('admin.public-settings.index')
            ->with('saved', "Public update {$update->title} saved.");
    }

    public function update(Request $request, PublicUpdate $publicUpdate): RedirectResponse
    {
        $before = $publicUpdate->only(['title', 'description', 'facebook_url', 'position', 'is_published']);
        $publicUpdate->update($this->validated($request));

        AdminActivityLog::record($request->user(), 'public_update.updated', $publicUpdate, [
            'before' => $before,
            'after' => $publicUpdate->fresh()->only(array_keys($before)),
        ]);

        return redirect()
            ->route('admin.public-settings.index')
            ->with('saved', "Public update {$publicUpdate->title} updated.");
    }

    public function updateSocial(Request $request): RedirectResponse
    {
        $payload = $this->validatedSocial($request);
        $settings = PublicSiteSetting::instance();
        $before = $settings->only(['facebook_url', 'instagram_url', 'youtube_url']);
        $settings->update($payload);

        AdminActivityLog::record($request->user(), 'public_settings.social.updated', $settings, [
            'before' => $before,
            'after' => $settings->fresh()->only(array_keys($before)),
        ]);

        return redirect()
            ->route('admin.public-settings.index')
            ->with('saved', 'Social media links saved.');
    }

    public function destroy(Request $request, PublicUpdate $publicUpdate): RedirectResponse
    {
        $title = $publicUpdate->title;

        AdminActivityLog::record($request->user(), 'public_update.deleted', $publicUpdate, [
            'title' => $title,
            'facebook_url' => $publicUpdate->facebook_url,
        ]);

        $publicUpdate->delete();

        return redirect()
            ->route('admin.public-settings.index')
            ->with('saved', "Public update {$title} removed.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge([
            'title' => trim((string) $request->input('title')),
            'description' => trim((string) $request->input('description')),
            'facebook_url' => PublicUpdate::normalizeFacebookUrl((string) $request->input('facebook_url')),
        ]);

        $validated = $request->validateWithBag('publicUpdate', [
            'title' => ['required', 'string', 'max:160', 'not_regex:/[<>"\'`;{}|\\\\]/u'],
            'description' => ['nullable', 'string', 'max:500', 'not_regex:/[<>"\'`;{}|\\\\]/u'],
            'facebook_url' => [
                'required',
                'string',
                'max:500',
                'url:https',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! PublicUpdate::isAllowedFacebookUrl((string) $value)) {
                        $fail('Use a public Facebook post, video, or reel link.');
                    }
                },
            ],
            'position' => ['required', 'integer', 'min:1', 'max:99'],
            'is_published' => ['nullable', 'boolean'],
        ], [
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
        ]);

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'facebook_url' => $validated['facebook_url'],
            'position' => (int) $validated['position'],
            'is_published' => $request->boolean('is_published'),
        ];
    }

    /** @return array<string, mixed> */
    private function validatedSocial(Request $request): array
    {
        $request->merge([
            'social_facebook_url' => PublicSiteSetting::normalizeHttpsUrl((string) $request->input('social_facebook_url')),
            'social_instagram_url' => PublicSiteSetting::normalizeHttpsUrl((string) $request->input('social_instagram_url')),
            'social_youtube_url' => PublicSiteSetting::normalizeHttpsUrl((string) $request->input('social_youtube_url')),
        ]);

        $validated = $request->validateWithBag('publicSocial', [
            'social_facebook_url' => $this->socialUrlRules('facebook', 'Use a public Facebook page or profile link.'),
            'social_instagram_url' => $this->socialUrlRules('instagram', 'Use a public Instagram profile link.'),
            'social_youtube_url' => $this->socialUrlRules('youtube', 'Use a public YouTube channel or video link.'),
        ]);

        return [
            'facebook_url' => $validated['social_facebook_url'] !== '' ? $validated['social_facebook_url'] : null,
            'instagram_url' => $validated['social_instagram_url'] !== '' ? $validated['social_instagram_url'] : null,
            'youtube_url' => $validated['social_youtube_url'] !== '' ? $validated['social_youtube_url'] : null,
        ];
    }

    /** @return list<mixed> */
    private function socialUrlRules(string $network, string $message): array
    {
        return [
            'nullable',
            'string',
            'max:500',
            function (string $attribute, mixed $value, \Closure $fail) use ($network, $message): void {
                $url = trim((string) $value);
                if ($url === '') {
                    return;
                }

                if (! PublicSiteSetting::isAllowedSocialUrl($url, $network)) {
                    $fail($message);
                }
            },
        ];
    }

    private function updateView(?PublicUpdate $editingUpdate = null): View
    {
        $nextPosition = (int) (PublicUpdate::query()->max('position') ?? 0) + 1;

        return view('admin.public-updates.index', [
            'updates' => PublicUpdate::query()->orderBy('position')->orderBy('id')->get(),
            'editingUpdate' => $editingUpdate,
            'nextPosition' => min($nextPosition, 99),
            'siteSettings' => PublicSiteSetting::current(),
        ]);
    }
}
