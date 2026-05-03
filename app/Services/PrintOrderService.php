<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\PrintOrder;
use Illuminate\Support\Facades\Storage;

class PrintOrderService
{
    /**
     * Lấy danh sách templates có sẵn
     */
    public function getAvailableTemplates(): array
    {
        return array_values(config('print_templates', []));
    }

    /**
     * Lấy thông tin template theo ID
     */
    public function getTemplate(string $templateId): object
    {
        $templates = config('print_templates', []);
        if (!isset($templates[$templateId])) {
            abort(404, 'Template not found');
        }
        return (object) $templates[$templateId];
    }

    /**
     * Tạo đơn hàng print mới
     */
    public function createOrder(
        int $userId,
        string $templateId,
        string $orderType,
        array $memberIds,
        ?string $shippingName,
        ?string $shippingPhone,
        ?string $shippingAddress,
        ?string $shippingCity,
        ?string $shippingDistrict,
        ?string $shippingWard,
        string $pdfPath,
        int $price
    ): PrintOrder {
        $order = PrintOrder::create([
            'user_id' => $userId,
            'template_id' => $templateId,
            'order_type' => $orderType,
            'member_ids' => json_encode($memberIds),
            'shipping_name' => $shippingName,
            'shipping_phone' => $shippingPhone,
            'shipping_address' => $shippingAddress,
            'shipping_city' => $shippingCity,
            'shipping_district' => $shippingDistrict,
            'shipping_ward' => $shippingWard,
            'pdf_path' => $pdfPath,
            'status' => 'pending',
            'total_price' => $price,
        ]);

        return $order;
    }

    /**
     * Lấy thông tin đơn hàng theo ID
     */
    public function getOrder(int $orderId): PrintOrder
    {
        $order = PrintOrder::with(['user'])->findOrFail($orderId);
        // Map template data from config
        $order->template = $this->getTemplate($order->template_id);
        return $order;
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateOrderStatus(PrintOrder $order, array $data): void
    {
        $order->update($data);
    }
}
