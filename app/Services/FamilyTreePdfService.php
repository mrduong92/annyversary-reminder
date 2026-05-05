<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FamilyTreePdfService
{
    /**
     * Convert SVG content → single-page A4 landscape PDF.
     *
     * Vấn đề khi dùng SVG file trực tiếp: Chromium print-to-pdf mặc định A4 portrait
     * → nội dung tràn sang trang 2. Fix: wrap SVG trong HTML với @page khai báo đúng
     * kích thước A4 landscape (297mm × 210mm), đồng thời scale SVG vừa khít trang.
     *
     * SVG template Gia_Pha_3 là 3508 × 2480 px = A4 landscape ở 300 DPI.
     */
    public function generate(string $svgContent, string $folder = 'download-unlocks'): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $uid      = Str::random(12);
        $htmlTemp = $tempDir . '/gia-pha-' . $uid . '.html';
        $pdfTemp  = $tempDir . '/gia-pha-' . $uid . '.pdf';

        // HTML wrapper — @page đảm bảo 1 trang A4 landscape, không margin, không header/footer
        $html = '<!DOCTYPE html>'
            . '<html><head><meta charset="utf-8"><style>'
            . '@page { size: 297mm 210mm; margin: 0; }'
            . 'html, body { margin: 0; padding: 0; width: 297mm; height: 210mm; overflow: hidden; }'
            . 'svg { display: block; width: 297mm !important; height: 210mm !important; }'
            . '</style></head>'
            . '<body>' . $svgContent . '</body></html>';

        file_put_contents($htmlTemp, $html);

        $command = sprintf(
            'chromium --headless --disable-gpu --no-sandbox --disable-setuid-sandbox --disable-dev-shm-usage'
            . ' --print-to-pdf=%s --print-to-pdf-no-header'
            . ' %s 2>&1',
            escapeshellarg($pdfTemp),
            escapeshellarg('file://' . $htmlTemp),
        );
        exec($command, $output, $exitCode);

        @unlink($htmlTemp);

        if ($exitCode !== 0 || !file_exists($pdfTemp)) {
            throw new \RuntimeException('Không thể tạo PDF: ' . implode("\n", $output));
        }

        $relativePath = $folder . '/gia-pha-' . $uid . '.pdf';
        Storage::disk('public')->put($relativePath, file_get_contents($pdfTemp));
        @unlink($pdfTemp);

        return $relativePath;
    }
}
