<?php

namespace App\Services\AI;

use App\Models\DocumentChunk;
use App\Models\FamilyDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Pgvector\Laravel\Distance;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class RagService
{
    private const CHUNK_SIZE    = 600;   // ký tự/chunk
    private const CHUNK_OVERLAP = 80;    // ký tự overlap
    private const TOP_K         = 5;     // số chunk trả về khi search

    // ── Embed ──────────────────────────────────────────────────

    /** Gọi Gemini text-embedding-004, trả array 768 floats */
    public function embed(string $text): array
    {
        $key   = config('services.gemini.api_key');
        $model = config('services.gemini.embedding_model', 'gemini-embedding-001');

        $resp = Http::timeout(20)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$key}",
            [
                'model'                => "models/{$model}",
                'content'              => ['parts' => [['text' => $text]]],
                'outputDimensionality' => 768,   // pgvector vector(768)
            ]
        );

        if (! $resp->successful()) {
            Log::error('Gemini embed error', ['body' => $resp->body()]);
            throw new \RuntimeException('Embedding failed: ' . $resp->body());
        }

        return $resp->json('embedding.values');
    }

    // ── Process document ───────────────────────────────────────

    /** Extract text, chunk, embed, lưu vào document_chunks */
    public function processDocument(FamilyDocument $doc): void
    {
        try {
            $text = $this->extractText($doc);

            if (empty(trim($text))) {
                $doc->update(['status' => 'failed']);
                return;
            }

            $chunks = $this->chunk($text);

            // Xóa chunks cũ nếu có (re-process)
            $doc->chunks()->delete();

            foreach ($chunks as $i => $chunkText) {
                $vector = $this->embed($chunkText);

                DocumentChunk::create([
                    'document_id' => $doc->id,
                    'chunk_index' => $i,
                    'content'     => $chunkText,
                    'embedding'   => $this->formatVector($vector),
                ]);
            }

            $doc->update(['status' => 'ready']);

        } catch (\Throwable $e) {
            Log::error('RAG processDocument failed', ['doc_id' => $doc->id, 'error' => $e->getMessage()]);
            $doc->update(['status' => 'failed']);
            throw $e;
        }
    }

    // ── Search ─────────────────────────────────────────────────

    /** Tìm các chunks liên quan nhất với query, trả về text context */
    public function search(string $query, int $familyGroupId, int $topK = self::TOP_K): string
    {
        $docIds = \App\Models\FamilyDocument::where('family_group_id', $familyGroupId)
            ->where('status', 'ready')
            ->pluck('id');

        if ($docIds->isEmpty()) {
            return '';
        }

        $queryVector = $this->embed($query);

        // Cosine similarity search qua pgvector
        $chunks = DocumentChunk::whereIn('document_id', $docIds)
            ->nearestNeighbors('embedding', $queryVector, Distance::Cosine)
            ->limit($topK)
            ->get();

        if ($chunks->isEmpty()) {
            return '';
        }

        return $chunks->pluck('content')->implode("\n\n---\n\n");
    }

    // ── Private ────────────────────────────────────────────────

    private function extractText(FamilyDocument $doc): string
    {
        $path = Storage::path($doc->path);

        return match (true) {
            str_ends_with(strtolower($doc->filename), '.pdf')  => $this->extractPdf($path),
            str_ends_with(strtolower($doc->filename), '.docx') => $this->extractDocx($path),
            str_ends_with(strtolower($doc->filename), '.doc')  => $this->extractDocx($path),
            default                                             => file_get_contents($path) ?: '',
        };
    }

    private function extractPdf(string $path): string
    {
        try {
            $parser = new PdfParser();
            $pdf    = $parser->parseFile($path);
            return $pdf->getText();
        } catch (\Throwable $e) {
            Log::warning('PDF parse error', ['error' => $e->getMessage()]);
            return '';
        }
    }

    private function extractDocx(string $path): string
    {
        try {
            $phpWord = WordIOFactory::load($path);
            $text    = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, 'getText')) {
                                $text .= $child->getText();
                            }
                        }
                        $text .= "\n";
                    }
                }
            }
            return $text;
        } catch (\Throwable $e) {
            Log::warning('DOCX parse error', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /** Tách text thành chunks có overlap */
    private function chunk(string $text): array
    {
        $text   = preg_replace('/\s+/', ' ', trim($text));
        $len    = mb_strlen($text);
        $chunks = [];
        $offset = 0;
        $step   = max(1, self::CHUNK_SIZE - self::CHUNK_OVERLAP); // luôn tiến về phía trước

        while ($offset < $len) {
            $chunk = mb_substr($text, $offset, self::CHUNK_SIZE);

            // Cắt tại dấu câu gần nhất để chunk tự nhiên hơn
            $chunkLen = mb_strlen($chunk);
            if ($offset + $chunkLen < $len && $chunkLen > self::CHUNK_OVERLAP * 2) {
                $lastBreak = max(
                    mb_strrpos($chunk, '. ') ?: 0,
                    mb_strrpos($chunk, '! ') ?: 0,
                    mb_strrpos($chunk, '? ') ?: 0,
                    mb_strrpos($chunk, "\n") ?: 0,
                );
                if ($lastBreak > self::CHUNK_SIZE * 0.5) {
                    $chunk = mb_substr($chunk, 0, $lastBreak + 1);
                }
            }

            if (mb_strlen(trim($chunk)) > 20) {
                $chunks[] = trim($chunk);
            }

            $offset += $step;
        }

        return $chunks;
    }

    /** Chuyển float array sang postgres vector format */
    private function formatVector(array $vector): string
    {
        return '[' . implode(',', $vector) . ']';
    }
}
