<?php

namespace App\Http\Controllers;

use App\Services\LunarCalendarService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(
        private readonly LunarCalendarService $lunar,
        private readonly SubscriptionService  $subscription,
    ) {}

    public function index(): View
    {
        $user          = Auth::user();
        $canImport     = $this->subscription->canImportImage($user);
        $activeFamilyGroup = active_group();

        return view('events.import', compact('canImport', 'activeFamilyGroup'));
    }

    /**
     * Nhận ảnh, gọi Gemini Vision, trả về JSON danh sách ngày giỗ để preview.
     */
    public function preview(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $this->subscription->canImportImage($user)) {
            return response()->json([
                'error' => 'Nhập ngày giỗ từ ảnh chỉ dành cho gói Đại Gia Đình.',
            ], 403);
        }

        $request->validate([
            'image' => ['required', 'image', 'max:10240'],
        ]);

        $file     = $request->file('image');
        $mimeType = $file->getMimeType();
        $base64   = base64_encode(file_get_contents($file->getRealPath()));

        $prompt = <<<'PROMPT'
Đây là ảnh ghi danh sách ngày giỗ / ngày kỵ của gia đình Việt Nam.
Hãy đọc và trích xuất TẤT CẢ ngày giỗ có trong ảnh.

Trả về JSON array (CHỈ JSON, không giải thích, không markdown):
[
  {
    "pronoun": "Danh xưng (VD: Cụ Ông, Cụ Bà, Ông, Bà, Bố, Mẹ, Cụ...)",
    "name": "Họ và tên người mất",
    "relationship": "Quan hệ với người trong nhà (VD: ông nội, bà ngoại, bố, mẹ...)",
    "lunar_day": 10,
    "lunar_month": 3,
    "date_type": "lunar"
  }
]

Quy tắc:
- Nếu là ngày âm lịch (có ghi "âm", "ÂL", không ghi gì): date_type = "lunar"
- Nếu là ngày dương lịch (có ghi "DL", "dương"): date_type = "solar"
- lunar_day và lunar_month phải là số nguyên dương
- Nếu không rõ tên: ghi "Không rõ"
- pronoun để "" nếu không có
- Nếu không tìm thấy ngày giỗ nào: trả về []
PROMPT;

        try {
            $key      = config('services.gemini.api_key');
            $model    = config('services.gemini.model', 'gemini-2.0-flash-lite');
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}",
                [
                    'contents' => [[
                        'parts' => [
                            ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64]],
                            ['text' => $prompt],
                        ],
                    ]],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature'        => 0.1,
                    ],
                ]
            );

            if (! $response->successful()) {
                Log::error('Gemini Vision error', ['body' => $response->body()]);
                return response()->json(['error' => 'Không thể phân tích ảnh. Vui lòng thử lại.'], 422);
            }

            $text   = $response->json('candidates.0.content.parts.0.text', '[]');
            $parsed = json_decode($text, true);

            if (! is_array($parsed)) {
                return response()->json(['error' => 'Không tìm thấy ngày giỗ nào trong ảnh.'], 422);
            }

            $events = collect($parsed)
                ->filter(fn ($e) =>
                    isset($e['lunar_day'], $e['lunar_month']) &&
                    is_numeric($e['lunar_day']) &&
                    is_numeric($e['lunar_month']) &&
                    (int) $e['lunar_day'] >= 1 &&
                    (int) $e['lunar_month'] >= 1
                )
                ->map(fn ($e) => [
                    'pronoun'      => trim($e['pronoun'] ?? ''),
                    'name'         => trim($e['name'] ?? 'Không rõ'),
                    'relationship' => trim($e['relationship'] ?? ''),
                    'lunar_day'    => (int) $e['lunar_day'],
                    'lunar_month'  => (int) $e['lunar_month'],
                    'date_type'    => in_array($e['date_type'] ?? '', ['lunar', 'solar'])
                        ? $e['date_type'] : 'lunar',
                ])
                ->values();

            if ($events->isEmpty()) {
                return response()->json(['error' => 'Không tìm thấy ngày giỗ hợp lệ trong ảnh.'], 422);
            }

            return response()->json(['events' => $events]);

        } catch (\Throwable $e) {
            Log::error('Import preview error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Có lỗi xảy ra. Vui lòng thử lại.'], 500);
        }
    }

    /**
     * Nhận JSON đã review từ user, bulk create MemorialEvents.
     * KHÔNG tạo FamilyMember — đây là tính năng riêng biệt.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'events'                   => ['required', 'array', 'min:1', 'max:50'],
            'events.*.pronoun'         => ['nullable', 'string', 'max:50'],
            'events.*.name'            => ['required', 'string', 'max:100'],
            'events.*.relationship'    => ['nullable', 'string', 'max:80'],
            'events.*.lunar_day'       => ['required', 'integer', 'min:1', 'max:31'],
            'events.*.lunar_month'     => ['required', 'integer', 'min:1', 'max:12'],
            'events.*.date_type'       => ['required', 'in:lunar,solar'],
        ]);

        $group   = active_group();
        $user    = Auth::user();
        $created = 0;
        $errors  = [];

        foreach ($request->events as $i => $data) {
            try {
                $day   = (int) $data['lunar_day'];
                $month = (int) $data['lunar_month'];
                $type  = $data['date_type'];

                $solarNext = $type === 'solar'
                    ? $this->lunar->nextSolarOccurrence($day, $month)
                    : $this->lunar->nextOccurrence($day, $month);

                // Tên hiển thị: ghép danh xưng + tên
                $displayName = trim(($data['pronoun'] ?? '') . ' ' . $data['name']);

                $group->memorialEvents()->create([
                    'user_id'        => $user->id,
                    'name'           => $displayName,
                    'relationship'   => $data['relationship'] ?? null,
                    'lunar_day'      => $day,
                    'lunar_month'    => $month,
                    'date_type'      => $type,
                    'solar_date_next'=> $solarNext,
                    'is_active'      => true,
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::error('Import confirm error', ['row' => $i, 'error' => $e->getMessage()]);
                $errors[] = 'Dòng ' . ($i + 1) . " ({$data['name']}): " . $e->getMessage();
            }
        }

        return response()->json([
            'created'  => $created,
            'errors'   => $errors,
            'redirect' => route('events.index'),
        ]);
    }
}
