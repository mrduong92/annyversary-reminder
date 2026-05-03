<?php

namespace App\Http\Controllers;

use App\Models\FamilyGroup;
use App\Models\FamilyMember;
use App\Services\FamilyTreeSvgService;
use App\Services\FamilyTreePdfService;
use App\Services\PrintOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class PrintOrderController extends Controller
{
    public function __construct(
        private readonly FamilyTreeSvgService $svgService,
        private readonly FamilyTreePdfService $pdfService,
        private readonly PrintOrderService $printOrderService,
    ) {}

    public function index(Request $request)
    {
        $templates = $this->printOrderService->getAvailableTemplates();
        
        $selectedTemplate = null;
        $svg = null;
        $familyGroup = FamilyGroup::where('user_id', Auth::id())->firstOrFail();
        
        if ($request->has('template_id')) {
            $selectedTemplate = $this->printOrderService->getTemplate($request->template_id);
            $members = $familyGroup->familyMembers;
            $svg = $this->svgService->generate($members, $selectedTemplate);
        }

        return view('print-orders.index', compact('templates', 'selectedTemplate', 'svg', 'familyGroup'));
    }

    public function order(Request $request)
    {
        $request->validate([
            'template_id' => ['required', 'string', Rule::in(array_keys(config('print_templates', [])))],
            'order_type' => ['required', 'string'],
            'shipping_name' => ['required_if:order_type,!=,digital', 'nullable', 'string', 'max:100'],
            'shipping_phone' => ['required_if:order_type,!=,digital', 'nullable', 'string', 'max:20'],
            'shipping_address' => ['required_if:order_type,!=,digital', 'nullable', 'string', 'max:500'],
        ]);

        $template = $this->printOrderService->getTemplate($request->template_id);
        
        // Auto-load all members
        $familyGroup = FamilyGroup::where('user_id', Auth::id())->firstOrFail();
        $members = $familyGroup->familyMembers;
        $memberIds = $members->pluck('id')->toArray();

        // Generate PDF
        $pdfPath = $this->pdfService->generate($members, $template);

        // Calculate price based on order_type
        $printOptions = config('print_options');
        $price = 0;
        if ($request->order_type === 'digital') {
            $price = $printOptions['digital']['price'] ?? 49000;
        } else {
            foreach ($printOptions['physical_options'] as $option) {
                if ($option['id'] === $request->order_type) {
                    $price = $option['price'];
                    break;
                }
            }
        }

        // Create print order
        $order = $this->printOrderService->createOrder(
            Auth::id(),
            $template->id,
            $request->order_type,
            $memberIds,
            $request->order_type === 'digital' ? null : $request->shipping_name,
            $request->order_type === 'digital' ? null : $request->shipping_phone,
            $request->order_type === 'digital' ? null : $request->shipping_address,
            null, null, null,
            $pdfPath,
            $price
        );

        return redirect()->route('print-orders.show', $order->id)
            ->with('success', 'Đã đặt hàng thành công! Chúng tôi sẽ liên hệ để xác nhận và gửi.');
    }

    public function show($id): View
    {
        $order = $this->printOrderService->getOrder($id);
        $this->authorizeOrder($order);

        return view('print-orders.show', compact('order'));
    }

    public function track($id, Request $request)
    {
        $order = $this->printOrderService->getOrder($id);
        $this->authorizeOrder($order);

        $order->update([
            'status' => $request->status,
            'tracking_number' => $request->tracking_number,
            'shipping_company' => $request->shipping_company,
            'shipping_fee' => $request->shipping_fee,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    private function authorizeOrder($order): void
    {
        abort_if($order->user_id !== Auth::id(), 403);
    }
}
