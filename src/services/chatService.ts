import api from "./api";
import type { ChatMessage } from "@/types";

interface ChatResponse {
  response: string;
}

export async function sendMessage(message: string): Promise<string> {
  const { data } = await api.post<ChatResponse>("/chat", { message });
  return data.response;
}

export async function getChatHistory(): Promise<ChatMessage[]> {
  const { data } = await api.get<ChatMessage[]>("/chat/history");
  return data;
}
