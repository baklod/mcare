<?php

namespace App\Services;

use App\Contracts\OfficialDocumentRenderer;
use App\Models\OfficialDocument;
use Spatie\Browsershot\Browsershot;

class BrowsershotOfficialDocumentRenderer implements OfficialDocumentRenderer
{
    public function render(OfficialDocument $document): string
    {
        $document->loadMissing([
            'application.batch',
            'application.competencyRecords.unit',
        ]);

        $view = $document->type === OfficialDocument::TYPE_TOR
            ? 'documents.pdf.tor'
            : 'documents.pdf.cotc';
        $html = view($view, [
            'document' => $document,
            'application' => $document->application,
            'organization' => config('official_documents.organization'),
            'logoDataUri' => $this->logoDataUri(),
            'cotcTemplateDataUri' => $document->type === OfficialDocument::TYPE_COTC
                ? $this->publicPngDataUri('assets/cotc-official-template.png')
                : null,
        ])->render();

        $browsershot = Browsershot::html($html)
            ->showBackground()
            ->allowFileAccess();

        if ($document->type === OfficialDocument::TYPE_TOR) {
            $browsershot->format('A4')->margins(0, 0, 0, 0);
        } else {
            $browsershot->paperSize(279.4, 215.9, 'mm')->margins(0, 0, 0, 0);
        }

        $this->applyConfiguredBinaries($browsershot);

        return $browsershot->pdf();
    }

    private function applyConfiguredBinaries(Browsershot $browsershot): void
    {
        $settings = config('official_documents.browsershot', []);

        if (filled($settings['node_binary'] ?? null)) {
            $browsershot->setNodeBinary($settings['node_binary']);
        }

        if (filled($settings['npm_binary'] ?? null)) {
            $browsershot->setNpmBinary($settings['npm_binary']);
        }

        $chromePath = $settings['chrome_path'] ?? null;

        if (blank($chromePath)) {
            $chromePath = $this->windowsBrowserPath();
        }

        if (filled($chromePath)) {
            $browsershot->setChromePath($chromePath);
        }
    }

    private function windowsBrowserPath(): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        // Prefer installed, policy-approved browsers before asking Puppeteer to download one.
        $candidates = [
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function logoDataUri(): string
    {
        return $this->publicPngDataUri('assets/official-logo.png');
    }

    private function publicPngDataUri(string $relativePath): string
    {
        $path = public_path($relativePath);

        if (! is_file($path)) {
            throw new \RuntimeException("Official document asset is missing: {$relativePath}");
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
