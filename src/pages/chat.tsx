import { useEffect, useRef, useState } from "react";
import { useAtom } from "jotai";
import { Box, Page, Text, Spinner } from "zmp-ui";
import { chatMessagesAtom } from "@/store/atoms";
import { sendMessage, getChatHistory } from "@/services/chatService";
import ChatBubble from "@/components/ChatBubble";
import ChatInput from "@/components/ChatInput";
import BottomNav from "@/components/BottomNav";
import type { ChatMessage } from "@/types";

export default function ChatPage() {
  const [messages, setMessages] = useAtom(chatMessagesAtom);
  const [sending, setSending] = useState(false);
  const [loading, setLoading] = useState(true);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    loadHistory();
  }, []);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const loadHistory = async () => {
    try {
      const history = await getChatHistory();
      setMessages(history);
    } catch (err) {
      console.error("Failed to load chat history:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleSend = async (text: string) => {
    // Add user message immediately
    const userMsg: ChatMessage = {
      id: Date.now(),
      user_id: 0,
      role: "user",
      content: text,
      created_at: new Date().toISOString(),
    };
    setMessages((prev) => [...prev, userMsg]);

    try {
      setSending(true);
      const response = await sendMessage(text);

      const aiMsg: ChatMessage = {
        id: Date.now() + 1,
        user_id: 0,
        role: "assistant",
        content: response,
        created_at: new Date().toISOString(),
      };
      setMessages((prev) => [...prev, aiMsg]);
    } catch (err) {
      const errorMsg: ChatMessage = {
        id: Date.now() + 1,
        user_id: 0,
        role: "assistant",
        content: "Xin lỗi, có lỗi xảy ra. Vui lòng thử lại.",
        created_at: new Date().toISOString(),
      };
      setMessages((prev) => [...prev, errorMsg]);
    } finally {
      setSending(false);
    }
  };

  return (
    <Page className="pb-16 bg-gray-50 flex flex-col h-screen">
      {/* Header */}
      <Box className="p-4 bg-white border-b">
        <Text.Title size="large">Chat AI</Text.Title>
        <Text size="xSmall" className="text-gray-500">
          Hỏi về ngày giỗ, chuyển đổi âm-dương lịch
        </Text>
      </Box>

      {/* Messages */}
      <Box className="flex-1 overflow-y-auto p-4" style={{ paddingBottom: 120 }}>
        {loading ? (
          <Box className="flex justify-center py-8">
            <Spinner />
          </Box>
        ) : messages.length === 0 ? (
          <Box className="text-center py-8">
            <Text className="text-gray-400 mb-2">
              Hãy hỏi tôi bất cứ điều gì về ngày giỗ!
            </Text>
            <Text size="xSmall" className="text-gray-300">
              VD: "Ngày giỗ ông nội năm nay vào ngày nào?"
            </Text>
          </Box>
        ) : (
          messages.map((msg) => (
            <ChatBubble
              key={msg.id}
              role={msg.role}
              content={msg.content}
              timestamp={msg.created_at}
            />
          ))
        )}

        {sending && (
          <Box className="flex justify-start mb-3">
            <Box className="bg-gray-100 rounded-2xl px-4 py-3 rounded-bl-sm">
              <Spinner size="small" />
            </Box>
          </Box>
        )}

        <div ref={messagesEndRef} />
      </Box>

      {/* Input - fixed at bottom above nav */}
      <Box className="fixed bottom-12 left-0 right-0 z-10">
        <ChatInput onSend={handleSend} disabled={sending} />
      </Box>

      <BottomNav />
    </Page>
  );
}
