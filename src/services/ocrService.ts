import api from "./api";
import type { OcrResult } from "@/types";

interface OcrResponse {
  items: OcrResult[];
}

export async function extractFromImage(
  imageBase64: string
): Promise<OcrResult[]> {
  const { data } = await api.post<OcrResponse>("/ocr/extract", {
    image: imageBase64,
  });
  return data.items;
}
