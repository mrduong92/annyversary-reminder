<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FamilyTreePdfService
{
    /**
     * Generate PDF từ data gia phả, lưu vào public disk.
     * Trả về đường dẫn relative trên public disk (vd: download-unlocks/gia-pha-xxx.pdf).
     */
    public function generate($members, $template, string $folder = 'download-unlocks'): string
    {
        $svgService = app(FamilyTreeSvgService::class);
        $svgUrl     = $svgService->generate($members, $template);

        $svgFilename = basename(parse_url($svgUrl, PHP_URL_PATH));
        $svgContent  = Storage::disk('public')->get('family-trees/' . $svgFilename);

        $relativePath = $this->convertSvgToPdf($svgContent, $folder);

        return $relativePath;
    }

    /**
     * Trả về URL công khai từ relative path trên public disk.
     */
    public function url(string $relativePath): string
    {
        return Storage::disk('public')->url($relativePath);
    }

    private function convertSvgToPdf(string $svgContent, string $folder): string
    {
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $uid      = Str::random(12);
        $svgTemp  = $tempDir . '/gia-pha-' . $uid . '.svg';
        $pdfTemp  = $tempDir . '/gia-pha-' . $uid . '.pdf';

        file_put_contents($svgTemp, $svgContent);

        $command = sprintf(
            'chromium --headless --disable-gpu --print-to-pdf=%s %s 2>&1',
            escapeshellarg($pdfTemp),
            escapeshellarg($svgTemp),
        );
        exec($command, $output, $exitCode);

        // Dọn SVG tạm bất kể kết quả
        @unlink($svgTemp);

        if ($exitCode !== 0 || ! file_exists($pdfTemp)) {
            throw new \RuntimeException('Không thể tạo PDF: ' . implode("\n", $output));
        }

        // Chuyển PDF vào public storage
        $relativePath = $folder . '/gia-pha-' . $uid . '.pdf';
        Storage::disk('public')->put($relativePath, file_get_contents($pdfTemp));
        @unlink($pdfTemp);

        return $relativePath;
    }
}
