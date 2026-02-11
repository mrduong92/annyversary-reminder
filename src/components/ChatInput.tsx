import { useState } from "react";
import { Box, Button, Icon, Input } from "zmp-ui";

interface Props {
  onSend: (message: string) => void;
  disabled?: boolean;
}

export default function ChatInput({ onSend, disabled = false }: Props) {
  const [text, setText] = useState("");

  const handleSend = () => {
    const trimmed = text.trim();
    if (!trimmed) return;
    onSend(trimmed);
    setText("");
  };

  return (
    <Box className="flex items-center space-x-2 p-3 bg-white border-t">
      <Box className="flex-1">
        <Input
          placeholder="Hỏi về ngày giỗ..."
          value={text}
          onChange={(e) => setText(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === "Enter" && !e.shiftKey) {
              e.preventDefault();
              handleSend();
            }
          }}
          disabled={disabled}
        />
      </Box>
      <Button
        variant="primary"
        size="small"
        onClick={handleSend}
        disabled={disabled || !text.trim()}
        icon={<Icon icon="zi-send-solid" />}
      />
    </Box>
  );
}
