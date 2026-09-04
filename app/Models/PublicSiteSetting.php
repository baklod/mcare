<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicSiteSetting extends Model
{
    protected $fillable = [
        'facebook_url',
        'instagram_url',
        'youtube_url',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'facebook_url' => null,
            'instagram_url' => null,
            'youtube_url' => null,
        ]);
    }

    public static function instance(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'facebook_url' => null,
            'instagram_url' => null,
            'youtube_url' => null,
        ]);
    }

    /** @return array{facebook: ?string, instagram: ?string, youtube: ?string} */
    public function socialLinks(): array
    {
        return [
            'facebook' => $this->facebook_url,
            'instagram' => $this->instagram_url,
            'youtube' => $this->youtube_url,
        ];
    }

    public static function normalizeHttpsUrl(string $url): string
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

        if ($host === '') {
            return '';
        }

        return 'https://'.$host.$path.$query;
    }

    /** @return list<string> */
    public static function allowedHosts(string $network): array
    {
        return match ($network) {
            'facebook' => [
                'facebook.com',
                'www.facebook.com',
                'm.facebook.com',
                'web.facebook.com',
                'fb.com',
                'www.fb.com',
            ],
            'instagram' => [
                'instagram.com',
                'www.instagram.com',
            ],
            'youtube' => [
                'youtube.com',
                'www.youtube.com',
                'm.youtube.com',
                'youtu.be',
                'www.youtu.be',
            ],
            default => [],
        };
    }

    public static function isAllowedSocialUrl(string $url, string $network): bool
    {
        $normalized = self::normalizeHttpsUrl($url);
        $host = strtolower((string) (parse_url($normalized, PHP_URL_HOST) ?: ''));

        return $normalized !== '' && in_array($host, self::allowedHosts($network), true);
    }
};
