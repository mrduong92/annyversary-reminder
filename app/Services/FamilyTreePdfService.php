<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\PrintTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FamilyTreePdfService
{
    /**
     * Generate PDF từ SVG gia phả
     */
    public function generate($members, $template): string
    {
        // Generate SVG trước
        $svgService = app(FamilyTreeSvgService::class);
        $svgUrl = $svgService->generate($members, $template);

        // Extract filename and get content from Storage
        $filename = basename($svgUrl);
        $svgContent = Storage::disk('public')->get('family-trees/' . $filename);

        // Convert SVG sang PDF
        $pdfPath = $this->convertSvgToPdf($svgContent, $template);

        return Storage::disk('public')->url('print-orders/' . basename($pdfPath));
    }

    /**
     * Convert SVG sang PDF
     */
    private function convertSvgToPdf(string $svgContent, $template): string
    {
        // Ensure temp directory exists
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        // Lưu SVG tạm
        $svgPath = storage_path('app/temp/family-tree-' . time() . '.svg');
        file_put_contents($svgPath, $svgContent);

        // Convert sang PDF
        $pdfPath = storage_path('app/temp/family-tree-' . time() . '.pdf');

        // Sử dụng browsershot để convert SVG sang PDF
        $command = sprintf(
            'chromium --headless --disable-gpu --print-to-pdf --output=%s %s',
            escapeshellarg($pdfPath),
            escapeshellarg($svgPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $errorOutput = is_array($output) ? implode("\n", $output) : $output;
            throw new \Exception('Không thể convert SVG sang PDF: ' . $errorOutput);
        }

        return $pdfPath;
    }
}
