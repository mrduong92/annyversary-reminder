<?php

namespace App\Http\Controllers;

use App\Models\DownloadUnlock;
use App\Models\FamilyGroup;
use App\Services\FamilyTreePdfService;
use App\Services\FamilyTreeSvgService;
use App\Services\PrintOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PrintOrderController extends Controller
{
    public function __construct(
        private readonly FamilyTreeSvgService $svgService,
        private readonly FamilyTreePdfService $pdfService,
        private readonly PrintOrderService    $printOrderService,
    ) {}

    /**
     * Trang chọn template + preview SVG + nút unlock/download/đặt in.
     */
    public function index(Request $request)
    {
        $templates   = $this->printOrderService->getAvailableTemplates();
        $familyGroup = FamilyGroup::where('user_id', Auth::id())->firstOrFail();

        $selectedTemplate = null;
        $svgUrl           = null;
        $isUnlocked       = false;
        $validUnlock      = null;

        if ($request->filled('template_id')) {
            $selectedTemplate = $this->printOrderService->getTemplate($request->template_id);

            if ($selectedTemplate) {
                $members = $familyGroup->familyMembers;

                // Generate SVG preview (stores to public disk, returns URL)
                $svgUrl = $this->svgService->generate($members, $selectedTemplate);

                $validUnlock = $familyGroup->validUnlock(Auth::id());
                $isUnlocked  = $validUnlock !== null;
            }
        }

        return view('print-orders.index', [
            'templates'        => $templates,
            'selectedTemplate' => $selectedTemplate,
            'svg'              => $svgUrl,
            'familyGroup'      => $familyGroup,
            'isUnlocked'       => $isUnlocked,
            'validUnlock'      => $validUnlock,
        ]);
    }

    /**
     * Xác nhận thanh toán → generate PDF → tạo DownloadUnlock.
     */
    public function confirmUnlock(Request $request)
    {
        $request->validate([
            'template_id'  => ['required', 'string', Rule::in(array_keys(config('print_templates', [])))],
            'payment_note' => ['nullable', 'string', 'max:255'],
        ]);

        $familyGroup = FamilyGroup::where('user_id', Auth::id())->firstOrFail();
        $template    = $this->printOrderService->getTemplate($request->template_id);

        // Generate SVG content → convert to PDF
        $members    = $familyGroup->familyMembers;
        $svgContent = $this->svgService->generateContent($members, $template);
        $filePath   = $this->pdfService->generate($svgContent);

        DownloadUnlock::create([
            'user_id'          => Auth::id(),
            'family_group_id'  => $familyGroup->id,
            'tree_snapshot_at' => $familyGroup->tree_updated_at ?? now(),
            'template_id'      => $request->template_id,
            'file_path'        => $filePath,
            'status'           => 'active',
            'amount'           => 49000,
            'payment_note'     => $request->payment_note,
        ]);

        return redirect()
            ->route('print-orders.index', ['template_id' => $request->template_id])
            ->with('success', 'Mở khóa thành công! Nhấn "Tải về" để tải file gia phả.');
    }

    /**
     * Download PDF (chỉ khi đã unlock và cây chưa sửa sau unlock).
     */
    public function download(Request $request)
    {
        $request->validate(['template_id' => ['required', 'string']]);

        $familyGroup = FamilyGroup::where('user_id', Auth::id())->firstOrFail();
        $validUnlock = $familyGroup->validUnlock(Auth::id());

        if (!$validUnlock) {
            return redirect()
                ->route('print-orders.index', ['template_id' => $request->template_id])
                ->withErrors(['unlock' => 'Bạn cần mở khóa tải về trước.']);
        }

        // Regenerate if file was lost
        if (!$validUnlock->file_path || !Storage::disk('public')->exists($validUnlock->file_path)) {
            $template   = $this->printOrderService->getTemplate($validUnlock->template_id ?? $request->template_id);
            $members    = $familyGroup->familyMembers;
            $svgContent = $this->svgService->generateContent($members, $template);
            $filePath   = $this->pdfService->generate($svgContent);
            $validUnlock->update(['file_path' => $filePath]);
        }

        $absolutePath = Storage::disk('public')->path($validUnlock->file_path);
        $downloadName = 'gia-pha-' . str_replace([' ', '/'], '-', $familyGroup->name ?? 'family') . '.pdf';

        return response()->download($absolutePath, $downloadName, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Legacy: xem đơn đặt in cũ.
     */
    public function show($id)
    {
        $order = $this->printOrderService->getOrder($id);
        abort_if($order->user_id !== Auth::id(), 403);
        return view('print-orders.show', compact('order'));
    }
}
