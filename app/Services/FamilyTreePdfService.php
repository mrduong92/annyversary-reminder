<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FamilyTreePdfService
{
    /**
     * Convert SVG content string to PDF.
     * Saves PDF to public storage and returns the relative path.
     *
     * @param  string $svgContent  Raw SVG markup
     * @param  string $folder      Subfolder inside public disk (default: download-unlocks)
     * @return string              Relative path on public disk, e.g. "download-unlocks/gia-pha-xxx.pdf"
     */
    public function generate(string $svgContent, string $folder = 'download-unlocks'): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $uid     = Str::random(12);
        $svgTemp = $tempDir . '/gia-pha-' . $uid . '.svg';
        $pdfTemp = $tempDir . '/gia-pha-' . $uid . '.pdf';

        file_put_contents($svgTemp, $svgContent);

        $command = sprintf(
            'chromium --headless --disable-gpu --print-to-pdf=%s %s 2>&1',
            escapeshellarg($pdfTemp),
            escapeshellarg($svgTemp),
        );
        exec($command, $output, $exitCode);

        @unlink($svgTemp);

        if ($exitCode !== 0 || !file_exists($pdfTemp)) {
            throw new \RuntimeException('Không thể tạo PDF: ' . implode("\n", $output));
        }

        $relativePath = $folder . '/gia-pha-' . $uid . '.pdf';
        Storage::disk('public')->put($relativePath, file_get_contents($pdfTemp));
        @unlink($pdfTemp);

        return $relativePath;
    }
}
