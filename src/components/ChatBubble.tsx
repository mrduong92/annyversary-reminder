import { Box, Text } from "zmp-ui";

interface Props {
  role: "user" | "assistant";
  content: string;
  timestamp?: string;
}

export default function ChatBubble({ role, content, timestamp }: Props) {
  const isUser = role === "user";

  return (
    <Box
      className={`flex ${isUser ? "justify-end" : "justify-start"} mb-3`}
    >
      <Box
        className={`max-w-[80%] rounded-2xl px-4 py-2 ${
          isUser
            ? "bg-blue-500 text-white rounded-br-sm"
            : "bg-gray-100 text-gray-800 rounded-bl-sm"
        }`}
      >
        <Text
          size="small"
          className={isUser ? "text-white" : "text-gray-800"}
          style={{ whiteSpace: "pre-wrap" }}
        >
          {content}
        </Text>
        {timestamp && (
          <Text
            size="xxSmall"
            className={`mt-1 ${isUser ? "text-blue-100" : "text-gray-400"}`}
          >
            {new Date(timestamp).toLocaleTimeString("vi-VN", {
              hour: "2-digit",
              minute: "2-digit",
            })}
          </Text>
        )}
      </Box>
    </Box>
  );
}
