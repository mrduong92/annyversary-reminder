import { useState } from "react";
import { Box, Button, Input, Text } from "zmp-ui";
import type { OcrResult } from "@/types";

interface Props {
  items: OcrResult[];
  onConfirm: (items: OcrResult[]) => void;
  onCancel: () => void;
  loading?: boolean;
}

export default function OcrResultEditor({
  items: initialItems,
  onConfirm,
  onCancel,
  loading = false,
}: Props) {
  const [items, setItems] = useState<OcrResult[]>(initialItems);

  const updateItem = (index: number, field: keyof OcrResult, value: any) => {
    const updated = [...items];
    updated[index] = { ...updated[index], [field]: value };
    setItems(updated);
  };

  const removeItem = (index: number) => {
    setItems(items.filter((_, i) => i !== index));
  };

  return (
    <Box className="space-y-4">
      <Text.Title size="small" className="font-semibold">
        Kết quả nhận dạng ({items.length} mục)
      </Text.Title>
      <Text size="xSmall" className="text-gray-500">
        Kiểm tra và chỉnh sửa trước khi lưu
      </Text>

      {items.map((item, index) => (
        <Box
          key={index}
          className="bg-white rounded-lg p-3 border border-gray-200 space-y-2"
        >
          <Box className="flex justify-between items-center">
            <Text size="small" className="font-medium">
              #{index + 1}
            </Text>
            <Button
              size="small"
              variant="tertiary"
              onClick={() => removeItem(index)}
            >
              Xoá
            </Button>
          </Box>
          <Input
            label="Tên"
            value={item.person_name}
            onChange={(e) => updateItem(index, "person_name", e.target.value)}
          />
          <Input
            label="Quan hệ"
            value={item.relationship}
            onChange={(e) => updateItem(index, "relationship", e.target.value)}
          />
          <Box className="flex space-x-2">
            <Input
              label="Ngày"
              type="number"
              value={String(item.lunar_day)}
              onChange={(e) =>
                updateItem(index, "lunar_day", parseInt(e.target.value, 10) || 0)
              }
            />
            <Input
              label="Tháng"
              type="number"
              value={String(item.lunar_month)}
              onChange={(e) =>
                updateItem(index, "lunar_month", parseInt(e.target.value, 10) || 0)
              }
            />
          </Box>
          <Input
            label="Ghi chú"
            value={item.notes || ""}
            onChange={(e) => updateItem(index, "notes", e.target.value)}
          />
        </Box>
      ))}

      {items.length === 0 && (
        <Box className="text-center py-8">
          <Text className="text-gray-400">Không nhận dạng được dữ liệu</Text>
        </Box>
      )}

      <Box className="flex space-x-3">
        <Button
          fullWidth
          variant="secondary"
          onClick={onCancel}
          disabled={loading}
        >
          Huỷ
        </Button>
        <Button
          fullWidth
          variant="primary"
          onClick={() => onConfirm(items)}
          loading={loading}
          disabled={items.length === 0}
        >
          Lưu tất cả ({items.length})
        </Button>
      </Box>
    </Box>
  );
}
