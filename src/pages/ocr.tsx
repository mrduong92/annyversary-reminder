import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAtom } from "jotai";
import { Box, Page, Text, Spinner, useSnackbar } from "zmp-ui";
import { anniversariesAtom, ocrResultAtom } from "@/store/atoms";
import { extractFromImage } from "@/services/ocrService";
import { createBulkAnniversaries } from "@/services/anniversaryService";
import ImageUploader from "@/components/ImageUploader";
import OcrResultEditor from "@/components/OcrResultEditor";
import BottomNav from "@/components/BottomNav";
import type { OcrResult } from "@/types";

type Step = "upload" | "processing" | "review";

export default function OcrUploadPage() {
  const navigate = useNavigate();
  const [, setAnniversaries] = useAtom(anniversariesAtom);
  const [ocrResult, setOcrResult] = useAtom(ocrResultAtom);
  const [step, setStep] = useState<Step>("upload");
  const [saving, setSaving] = useState(false);
  const { openSnackbar } = useSnackbar();

  const handleImageSelected = async (base64: string) => {
    try {
      setStep("processing");
      const results = await extractFromImage(base64);
      setOcrResult(results);
      setStep("review");
    } catch (err) {
      openSnackbar({ text: "Lỗi nhận dạng ảnh", type: "error" });
      setStep("upload");
    }
  };

  const handleConfirm = async (items: OcrResult[]) => {
    try {
      setSaving(true);
      const created = await createBulkAnniversaries(
        items.map((item) => ({
          person_name: item.person_name,
          relationship: item.relationship,
          lunar_day: item.lunar_day,
          lunar_month: item.lunar_month,
          notes: item.notes,
          source: "ocr" as const,
        }))
      );
      setAnniversaries((prev) => [...prev, ...created]);
      openSnackbar({
        text: `Đã lưu ${created.length} ngày giỗ`,
        type: "success",
      });
      navigate("/list");
    } catch (err) {
      openSnackbar({ text: "Lỗi khi lưu", type: "error" });
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    setOcrResult([]);
    setStep("upload");
  };

  return (
    <Page className="pb-16 bg-gray-50">
      <Box className="p-4">
        <Text.Title size="large" className="mb-2">
          Nhận dạng ảnh (AI)
        </Text.Title>
        <Text size="small" className="text-gray-500 mb-4">
          Chụp ảnh danh sách ngày giỗ, AI sẽ tự động nhận dạng
        </Text>

        {step === "upload" && (
          <ImageUploader onImageSelected={handleImageSelected} />
        )}

        {step === "processing" && (
          <Box className="flex flex-col items-center justify-center py-12">
            <Spinner />
            <Text className="mt-4 text-gray-500">
              AI đang nhận dạng...
            </Text>
          </Box>
        )}

        {step === "review" && (
          <OcrResultEditor
            items={ocrResult}
            onConfirm={handleConfirm}
            onCancel={handleCancel}
            loading={saving}
          />
        )}
      </Box>
      <BottomNav />
    </Page>
  );
}
