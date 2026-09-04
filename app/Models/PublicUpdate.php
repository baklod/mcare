<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicUpdate extends Model
{
    public const LANDING_LIMIT = 3;

    protected $fillable = [
        'title',
        'description',
        'facebook_url',
        'position',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /** @return list<string> */
    public static function allowedHosts(): array
    {
        return [
            'facebook.com',
            'www.facebook.com',
            'm.facebook.com',
            'web.facebook.com',
            'fb.watch',
            'www.fb.watch',
            'm.fb.watch',
        ];
    }

    public static function normalizeFacebookUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (! preg_match('/\Ahttps?:\/\//i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return 'https://'.$host.$path.$query;
    }

    public static function isAllowedFacebookUrl(string $url): bool
    {
        $normalized = self::normalizeFacebookUrl($url);
        $host = strtolower((string) (parse_url($normalized, PHP_URL_HOST) ?: ''));

        return $normalized !== '' && in_array($host, self::allowedHosts(), true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeForLanding(Builder $query): Builder
    {
        return $query->published()
            ->orderBy('position')
            ->orderBy('id')
            ->limit(self::LANDING_LIMIT);
    }

    public function embedSrc(): string
    {
        $href = rawurlencode($this->facebook_url);
        $plugin = $this->usesVideoPlugin() ? 'video' : 'post';

        return "https://www.facebook.com/plugins/{$plugin}.php?href={$href}&show_text=false&width=560";
    }

    public function usesVideoPlugin(): bool
    {
        $url = strtolower($this->facebook_url);

        return str_contains($url, '/videos/')
            || str_contains($url, '/watch')
            || str_contains($url, '/reel')
            || str_contains($url, 'fb.watch')
            || str_contains($url, '/share/v/');
    }
}
