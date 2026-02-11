import { useEffect, useState } from "react";
import { Box, Text, Button, Icon, Spinner } from "zmp-ui";
import { getSubscribers, removeSubscriber } from "@/services/groupService";
import type { Subscriber } from "@/types";

interface Props {
  groupId: number;
}

export default function SubscriberList({ groupId }: Props) {
  const [subscribers, setSubscribers] = useState<Subscriber[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadSubscribers();
  }, [groupId]);

  const loadSubscribers = async () => {
    try {
      setLoading(true);
      const data = await getSubscribers(groupId);
      setSubscribers(data);
    } catch (err) {
      console.error("Failed to load subscribers:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleRemove = async (userId: number) => {
    try {
      await removeSubscriber(groupId, userId);
      setSubscribers((prev) => prev.filter((s) => s.user_id !== userId));
    } catch (err) {
      console.error("Failed to remove subscriber:", err);
    }
  };

  if (loading) {
    return (
      <Box className="flex justify-center py-4">
        <Spinner />
      </Box>
    );
  }

  if (subscribers.length === 0) {
    return (
      <Box className="text-center py-4">
        <Text size="small" className="text-gray-400">
          Chưa có thành viên nào
        </Text>
      </Box>
    );
  }

  return (
    <Box className="space-y-2">
      <Text size="small" className="font-semibold text-gray-600 mb-2">
        Thành viên ({subscribers.length})
      </Text>
      {subscribers.map((sub) => (
        <Box
          key={sub.user_id}
          className="flex items-center justify-between bg-white rounded-lg p-3 border border-gray-100"
        >
          <Box className="flex items-center space-x-3">
            {sub.avatar_url ? (
              <img
                src={sub.avatar_url}
                alt=""
                className="w-8 h-8 rounded-full"
              />
            ) : (
              <Box className="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                <Icon icon="zi-user" size={16} />
              </Box>
            )}
            <Text size="small">{sub.display_name || "Người dùng"}</Text>
          </Box>
          <Button
            size="small"
            variant="tertiary"
            onClick={() => handleRemove(sub.user_id)}
          >
            <Icon icon="zi-close" size={16} />
          </Button>
        </Box>
      ))}
    </Box>
  );
}
