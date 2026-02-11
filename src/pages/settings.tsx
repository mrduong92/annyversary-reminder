import { useEffect, useState } from "react";
import { useAtom } from "jotai";
import { Box, Page, Switch, Text, useSnackbar } from "zmp-ui";
import { defaultEventsAtom, userAtom } from "@/store/atoms";
import { getDefaults, updateDefaults } from "@/services/reminderService";
import BottomNav from "@/components/BottomNav";

export default function SettingsPage() {
  const [user] = useAtom(userAtom);
  const [defaults, setDefaults] = useAtom(defaultEventsAtom);
  const [loading, setLoading] = useState(true);
  const { openSnackbar } = useSnackbar();

  useEffect(() => {
    loadDefaults();
  }, []);

  const loadDefaults = async () => {
    try {
      const data = await getDefaults();
      setDefaults(data);
    } catch (err) {
      console.error("Failed to load defaults:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleToggle = async (key: "ram_15" | "mung_1", value: boolean) => {
    try {
      const updated = await updateDefaults({ [key]: value });
      setDefaults(updated);
      openSnackbar({ text: "Đã cập nhật", type: "success" });
    } catch {
      openSnackbar({ text: "Lỗi cập nhật", type: "error" });
    }
  };

  return (
    <Page className="pb-16 bg-gray-50">
      <Box className="p-4">
        <Text.Title size="large" className="mb-4">
          Cài đặt
        </Text.Title>

        {/* User info */}
        {user && (
          <Box className="bg-white rounded-lg p-4 mb-4 flex items-center space-x-3">
            {user.avatar_url && (
              <img
                src={user.avatar_url}
                alt="Avatar"
                className="w-12 h-12 rounded-full"
              />
            )}
            <Box>
              <Text className="font-medium">{user.display_name}</Text>
              <Text size="xSmall" className="text-gray-500">
                Zalo ID: {user.zalo_uid}
              </Text>
            </Box>
          </Box>
        )}

        {/* Default events */}
        <Text.Title size="small" className="mb-3 font-semibold">
          Nhắc mặc định
        </Text.Title>

        <Box className="bg-white rounded-lg divide-y">
          <Box className="flex items-center justify-between p-4">
            <Box>
              <Text className="font-medium">Rằm (ngày 15 ÂL)</Text>
              <Text size="xSmall" className="text-gray-500">
                Nhắc trước 7, 3, 1 ngày mỗi tháng
              </Text>
            </Box>
            <Switch
              checked={defaults.ram_15}
              onChange={(e) =>
                handleToggle("ram_15", (e.target as HTMLInputElement).checked)
              }
            />
          </Box>

          <Box className="flex items-center justify-between p-4">
            <Box>
              <Text className="font-medium">Mùng 1 (ngày 1 ÂL)</Text>
              <Text size="xSmall" className="text-gray-500">
                Nhắc trước 7, 3, 1 ngày mỗi tháng
              </Text>
            </Box>
            <Switch
              checked={defaults.mung_1}
              onChange={(e) =>
                handleToggle("mung_1", (e.target as HTMLInputElement).checked)
              }
            />
          </Box>
        </Box>

        {/* Reminder schedule info */}
        <Box className="mt-4 bg-blue-50 rounded-lg p-4">
          <Text size="small" className="font-medium text-blue-800">
            Lịch nhắc
          </Text>
          <Text size="xSmall" className="text-blue-600 mt-1">
            Mỗi ngày giỗ sẽ được nhắc trước 7 ngày, 3 ngày và 1 ngày. Thông
            báo gửi lúc 7:00 sáng.
          </Text>
        </Box>

        {/* App info */}
        <Box className="mt-6 text-center">
          <Text size="xSmall" className="text-gray-400">
            Nhắc Lịch Giỗ v1.0.0
          </Text>
          <Text size="xxSmall" className="text-gray-300">
            Sử dụng thuật toán Hồ Ngọc Đức cho lịch âm
          </Text>
        </Box>
      </Box>

      <BottomNav />
    </Page>
  );
}
