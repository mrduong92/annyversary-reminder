import { useState } from "react";
import { Box, Button, Icon, Text } from "zmp-ui";

interface Props {
  onImageSelected: (base64: string) => void;
  loading?: boolean;
}

export default function ImageUploader({
  onImageSelected,
  loading = false,
}: Props) {
  const [preview, setPreview] = useState<string | null>(null);

  const handleChooseImage = async () => {
    try {
      // Try zmp-sdk chooseImage first
      const { chooseImage } = await import("zmp-sdk");
      const { filePaths } = await chooseImage({
        count: 1,
        sourceType: ["album", "camera"],
      });

      if (filePaths && filePaths.length > 0) {
        // Convert file path to base64
        const response = await fetch(filePaths[0]);
        const blob = await response.blob();
        const reader = new FileReader();
        reader.onloadend = () => {
          const base64 = reader.result as string;
          setPreview(base64);
          onImageSelected(base64);
        };
        reader.readAsDataURL(blob);
      }
    } catch {
      // Fallback to HTML file input for development
      const input = document.createElement("input");
      input.type = "file";
      input.accept = "image/*";
      input.onchange = (e) => {
        const file = (e.target as HTMLInputElement).files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onloadend = () => {
          const base64 = reader.result as string;
          setPreview(base64);
          onImageSelected(base64);
        };
        reader.readAsDataURL(file);
      };
      input.click();
    }
  };

  return (
    <Box className="space-y-3">
      {preview ? (
        <Box className="relative">
          <img
            src={preview}
            alt="Preview"
            className="w-full rounded-lg max-h-64 object-contain bg-gray-100"
          />
          <Button
            size="small"
            variant="secondary"
            className="absolute top-2 right-2"
            onClick={() => {
              setPreview(null);
            }}
            icon={<Icon icon="zi-close" />}
          />
        </Box>
      ) : (
        <Box
          className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-400 transition-colors"
          onClick={handleChooseImage}
        >
          <Icon icon="zi-camera" className="text-4xl text-gray-400 mb-2" />
          <Text className="text-gray-500">
            Chụp ảnh hoặc chọn từ thư viện
          </Text>
          <Text size="xSmall" className="text-gray-400 mt-1">
            Hỗ trợ ảnh chụp danh sách ngày giỗ viết tay hoặc in
          </Text>
        </Box>
      )}

      {preview && (
        <Button
          fullWidth
          variant="primary"
          onClick={handleChooseImage}
          loading={loading}
        >
          Chọn ảnh khác
        </Button>
      )}
    </Box>
  );
}
