import { Box, Text, Icon } from "zmp-ui";
import type { AnniversaryGroup } from "@/types";

interface Props {
  group: AnniversaryGroup & { owner_name?: string; owner_avatar?: string };
  isOwned: boolean;
  onClick: () => void;
}

export default function GroupCard({ group, isOwned, onClick }: Props) {
  return (
    <Box
      className="bg-white rounded-lg p-4 mb-3 shadow-sm border border-gray-100 cursor-pointer"
      onClick={onClick}
    >
      <Box className="flex items-center justify-between">
        <Box className="flex items-center space-x-3">
          <Box className="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
            <Text className="text-red-600 text-lg">
              {group.is_default ? "📋" : "👨‍👩‍👧‍👦"}
            </Text>
          </Box>
          <Box>
            <Text.Title size="small" className="font-semibold">
              {group.name}
            </Text.Title>
            <Text size="xSmall" className="text-gray-500">
              {group.anniversary_count} ngày giỗ
              {group.subscriber_count > 0 &&
                ` · ${group.subscriber_count} thành viên`}
            </Text>
            {!isOwned && group.owner_name && (
              <Text size="xSmall" className="text-gray-400">
                Của: {group.owner_name}
              </Text>
            )}
          </Box>
        </Box>
        <Box className="flex items-center space-x-2">
          {group.share_code && isOwned && (
            <Box className="bg-green-100 rounded-full px-2 py-0.5">
              <Text size="xxSmall" className="text-green-700">
                Đang chia sẻ
              </Text>
            </Box>
          )}
          <Icon icon="zi-chevron-right" size={20} />
        </Box>
      </Box>
    </Box>
  );
}
