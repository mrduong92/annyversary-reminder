import { useEffect, useState } from "react";
import { useAtom } from "jotai";
import { useNavigate } from "react-router-dom";
import { Box, Button, Icon, Page, Text, Spinner } from "zmp-ui";
import { anniversariesAtom, remindersAtom, userAtom, ownedGroupsAtom, subscribedGroupsAtom } from "@/store/atoms";
import { getAnniversaries } from "@/services/anniversaryService";
import { getUpcomingReminders } from "@/services/reminderService";
import { getGroups } from "@/services/groupService";
import AnniversaryCard from "@/components/AnniversaryCard";
import BottomNav from "@/components/BottomNav";
import { getDaysUntilLabel } from "@/utils/formatters";

export default function HomePage() {
  const navigate = useNavigate();
  const [user] = useAtom(userAtom);
  const [anniversaries, setAnniversaries] = useAtom(anniversariesAtom);
  const [reminders, setReminders] = useAtom(remindersAtom);
  const [, setOwnedGroups] = useAtom(ownedGroupsAtom);
  const [, setSubscribedGroups] = useAtom(subscribedGroupsAtom);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [annData, remData, groupsData] = await Promise.all([
        getAnniversaries(),
        getUpcomingReminders(30),
        getGroups(),
      ]);
      setAnniversaries(annData);
      setReminders(remData);
      setOwnedGroups(groupsData.owned);
      setSubscribedGroups(groupsData.subscribed);
    } catch (err) {
      console.error("Failed to load data:", err);
    } finally {
      setLoading(false);
    }
  };

  // Get next 5 upcoming anniversaries by solar_date
  const upcoming = [...anniversaries]
    .filter((a) => a.solar_date)
    .sort(
      (a, b) =>
        new Date(a.solar_date!).getTime() - new Date(b.solar_date!).getTime()
    )
    .filter((a) => new Date(a.solar_date!) >= new Date())
    .slice(0, 5);

  return (
    <Page className="pb-16 bg-gray-50">
      {/* Header */}
      <Box className="bg-gradient-to-r from-red-600 to-red-500 text-white p-6 rounded-b-2xl">
        <Text.Title size="large" className="text-white">
          Nhắc Lịch Giỗ
        </Text.Title>
        <Text size="small" className="text-red-100 mt-1">
          {user?.display_name
            ? `Xin chào, ${user.display_name}`
            : "Quản lý ngày giỗ gia đình"}
        </Text>

        {/* Stats */}
        <Box className="flex mt-4 space-x-4">
          <Box className="flex-1 bg-white/20 rounded-lg p-3 text-center">
            <Text className="text-2xl font-bold text-white">
              {anniversaries.length}
            </Text>
            <Text size="xxSmall" className="text-red-100">
              Ngày giỗ
            </Text>
          </Box>
          <Box className="flex-1 bg-white/20 rounded-lg p-3 text-center">
            <Text className="text-2xl font-bold text-white">
              {reminders.filter((r) => r.status === "pending").length}
            </Text>
            <Text size="xxSmall" className="text-red-100">
              Nhắc sắp tới
            </Text>
          </Box>
        </Box>
      </Box>

      <Box className="p-4 space-y-4">
        {/* Quick actions */}
        <Box className="flex space-x-3">
          <Button
            fullWidth
            variant="primary"
            size="small"
            onClick={() => navigate("/add")}
            prefixIcon={<Icon icon="zi-plus" />}
          >
            Thêm thủ công
          </Button>
          <Button
            fullWidth
            variant="secondary"
            size="small"
            onClick={() => navigate("/ocr")}
            prefixIcon={<Icon icon="zi-camera" />}
          >
            Chụp ảnh (AI)
          </Button>
        </Box>

        {/* Upcoming */}
        <Box>
          <Text.Title size="small" className="mb-3 font-semibold">
            Sắp tới
          </Text.Title>

          {loading ? (
            <Box className="flex justify-center py-8">
              <Spinner />
            </Box>
          ) : upcoming.length > 0 ? (
            upcoming.map((a) => <AnniversaryCard key={a.id} anniversary={a} />)
          ) : (
            <Box className="text-center py-8 bg-white rounded-lg">
              <Text className="text-gray-400">
                Chưa có ngày giỗ nào
              </Text>
              <Button
                variant="tertiary"
                size="small"
                className="mt-2"
                onClick={() => navigate("/add")}
              >
                Thêm ngay
              </Button>
            </Box>
          )}
        </Box>

        {/* Upcoming reminders */}
        {reminders.length > 0 && (
          <Box>
            <Text.Title size="small" className="mb-3 font-semibold">
              Lịch nhắc
            </Text.Title>
            {reminders.slice(0, 5).map((r) => (
              <Box
                key={r.id}
                className="bg-white rounded-lg p-3 mb-2 flex items-center justify-between border border-gray-100"
              >
                <Box>
                  <Text size="small" className="font-medium">
                    {r.person_name || r.event_type}
                  </Text>
                  <Text size="xSmall" className="text-gray-500">
                    {r.lunar_day && r.lunar_month
                      ? `${r.lunar_day}/${r.lunar_month} ÂL`
                      : r.event_type === "ram_15"
                      ? "Rằm"
                      : "Mùng 1"}
                  </Text>
                </Box>
                <Box className="text-right">
                  <Text size="xSmall" className="text-blue-600 font-medium">
                    {getDaysUntilLabel(r.remind_date)}
                  </Text>
                </Box>
              </Box>
            ))}
          </Box>
        )}
      </Box>

      <BottomNav />
    </Page>
  );
}
