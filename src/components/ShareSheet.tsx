import { useState } from "react";
import { Box, Button, Text, Modal, Icon } from "zmp-ui";
import { shareGroup, revokeShare } from "@/services/groupService";
import { shareViaZalo } from "@/services/shareService";
import type { AnniversaryGroup } from "@/types";

interface Props {
  group: AnniversaryGroup;
  visible: boolean;
  onClose: () => void;
  onUpdate: (group: AnniversaryGroup) => void;
}

export default function ShareSheet({
  group,
  visible,
  onClose,
  onUpdate,
}: Props) {
  const [loading, setLoading] = useState(false);
  const [revoking, setRevoking] = useState(false);

  const handleShare = async () => {
    try {
      setLoading(true);
      let code = group.share_code;
      if (!code) {
        const result = await shareGroup(group.id);
        code = result.share_code;
        onUpdate({ ...group, share_code: code });
      }
      await shareViaZalo(code, group.name);
    } catch (err) {
      console.error("Share error:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleRevoke = async () => {
    try {
      setRevoking(true);
      await revokeShare(group.id);
      onUpdate({ ...group, share_code: null, subscriber_count: 0 });
      onClose();
    } catch (err) {
      console.error("Revoke error:", err);
    } finally {
      setRevoking(false);
    }
  };

  return (
    <Modal visible={visible} onClose={onClose} title="Chia sẻ nhóm">
      <Box className="p-4 space-y-4">
        <Box className="text-center">
          <Text.Title size="small">{group.name}</Text.Title>
          <Text size="small" className="text-gray-500 mt-1">
            {group.anniversary_count} ngày giỗ
          </Text>
        </Box>

        <Button
          fullWidth
          variant="primary"
          onClick={handleShare}
          loading={loading}
          prefixIcon={<Icon icon="zi-share" />}
        >
          Chia sẻ qua Zalo
        </Button>

        {group.share_code && (
          <>
            <Box className="bg-gray-50 rounded-lg p-3 text-center">
              <Text size="xSmall" className="text-gray-500">
                Mã chia sẻ
              </Text>
              <Text className="font-mono font-bold text-lg mt-1">
                {group.share_code}
              </Text>
            </Box>

            <Button
              fullWidth
              variant="tertiary"
              onClick={handleRevoke}
              loading={revoking}
              className="text-red-600"
            >
              Thu hồi chia sẻ
            </Button>
            <Text size="xSmall" className="text-gray-400 text-center">
              Thu hồi sẽ xoá quyền truy cập của tất cả thành viên
            </Text>
          </>
        )}
      </Box>
    </Modal>
  );
}
