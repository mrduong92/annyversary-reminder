<?php

namespace App\Http\Controllers;

use App\Services\LunarCalendarService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(
        private readonly LunarCalendarService $lunar,
        private readonly SubscriptionService $subscription
    ) {}

    public function index(): View
    {
        return view('events.import');
    }

    /**
     * Nhận ảnh, gọi Gemini Vision, trả về JSON danh sách ngày giỗ để preview.
     */
    public function preview(Request $request): JsonResponse
    {
        if (\Illuminate\Support\Facades\Gate::denies('import-image')) {
            return response()->json(['error' => 'Tính năng nhập từ ảnh chỉ dành cho gói Premium.'], 403);
        }

        $request->validate([
            'image' => ['required', 'image', 'max:10240'], // max 10MB
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
    "pronoun": "Danh xưng (VD: Cụ, Ông, Bà...)",
    "name": "Tên người mất đầy đủ",
    "relationship": "Quan hệ (VD: ông nội, bà ngoại, bố, mẹ...)",
    "lunar_day": 10,
    "lunar_month": 3,
    "date_type": "lunar"
  }
]

Quy tắc:
- Nếu là ngày âm lịch: date_type = "lunar"
- Nếu là ngày dương lịch: date_type = "solar"
- Nếu không xác định được: mặc định lunar
- lunar_day và lunar_month phải là số nguyên
- Nếu không rõ tên: ghi "Không rõ"
- pronoun có thể để trống ("") nếu không có danh xưng rõ ràng
- Nếu không có ngày giỗ nào: trả về []
PROMPT;

        try {
            $key      = config('services.gemini.api_key');
            $model    = 'gemini-2.5-flash';
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
            $events = json_decode($text, true);

            if (! is_array($events)) {
                return response()->json(['error' => 'Không tìm thấy ngày giỗ nào trong ảnh.'], 422);
            }

            // Validate và làm sạch từng record
            $cleaned = collect($events)->filter(fn ($e) =>
                isset($e['lunar_day'], $e['lunar_month']) &&
                is_numeric($e['lunar_day']) &&
                is_numeric($e['lunar_month'])
            )->map(fn ($e) => [
                'pronoun'      => trim($e['pronoun'] ?? ''),
                'name'         => trim($e['name'] ?? 'Không rõ'),
                'relationship' => trim($e['relationship'] ?? ''),
                'lunar_day'    => (int) $e['lunar_day'],
                'lunar_month'  => (int) $e['lunar_month'],
                'date_type'    => in_array($e['date_type'] ?? '', ['lunar', 'solar']) ? $e['date_type'] : 'lunar',
            ])->values();

            if ($cleaned->isEmpty()) {
                return response()->json(['error' => 'Không tìm thấy ngày giỗ hợp lệ trong ảnh.'], 422);
            }

            return response()->json(['events' => $cleaned]);

        } catch (\Throwable $e) {
            Log::error('Import preview error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Có lỗi xảy ra. Vui lòng thử lại.'], 500);
        }
    }

    /**
     * Nhận JSON đã edit từ user, bulk create events.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'events'                   => ['required', 'array', 'min:1', 'max:50'],
            'events.*.pronoun'         => ['nullable', 'string', 'max:50'],
            'events.*.name'            => ['required', 'string', 'max:100'],
            'events.*.relationship'    => ['nullable', 'string', 'max:50'],
            'events.*.lunar_day'       => ['required', 'integer', 'min:1', 'max:31'],
            'events.*.lunar_month'     => ['required', 'integer', 'min:1', 'max:12'],
            'events.*.date_type'       => ['required', 'in:lunar,solar'],
        ]);

        $group   = active_group();
        $user    = auth()->user();
        $created = 0;
        $errors  = [];

        foreach ($request->events as $i => $data) {
            if (! $this->subscription->canAddEvent($user)) {
                $errors[] = "Dòng " . ($i + 1) . " ({$data['name']}): Đã đạt giới hạn số lượng ngày giỗ của gói hiện tại.";
                continue;
            }

            try {
                $day   = (int) $data['lunar_day'];
                $month = (int) $data['lunar_month'];
                $type  = $data['date_type'];

                $solarNext = $type === 'solar'
                    ? $this->lunar->nextSolarOccurrence($day, $month)
                    : $this->lunar->nextOccurrence($day, $month);

                $member = $group->members()->create([
                    'user_id'      => $user->id,
                    'name'         => $data['name'],
                    'pronoun'      => $data['pronoun'] ?? null,
                    'relationship' => $data['relationship'] ?? null,
                    'gender'       => 'unknown',
                ]);

                $group->memorialEvents()->create([
                    'user_id'          => $user->id,
                    'family_member_id' => $member->id,
                    'lunar_day'        => $day,
                    'lunar_month'      => $month,
                    'date_type'        => $type,
                    'solar_date_next'  => $solarNext,
                    'is_active'        => true,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Dòng " . ($i + 1) . " ({$data['name']}): " . $e->getMessage();
            }
        }

        return response()->json([
            'created'  => $created,
            'errors'   => $errors,
            'redirect' => route('events.index'),
        ]);
    }
}
