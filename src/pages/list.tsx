import { useEffect, useState } from "react";
import { useAtom } from "jotai";
import { useNavigate } from "react-router-dom";
import { Box, Button, Icon, Page, Text, Spinner, useSnackbar } from "zmp-ui";
import { anniversariesAtom, ownedGroupsAtom, subscribedGroupsAtom } from "@/store/atoms";
import {
  getAnniversaries,
  deleteAnniversary,
} from "@/services/anniversaryService";
import { getGroups } from "@/services/groupService";
import AnniversaryCard from "@/components/AnniversaryCard";
import BottomNav from "@/components/BottomNav";
import { getLunarMonthName } from "@/utils/lunarCalendar";
import type { Anniversary } from "@/types";

export default function AnniversaryListPage() {
  const navigate = useNavigate();
  const [anniversaries, setAnniversaries] = useAtom(anniversariesAtom);
  const [ownedGroups, setOwnedGroups] = useAtom(ownedGroupsAtom);
  const [subscribedGroups, setSubscribedGroups] = useAtom(subscribedGroupsAtom);
  const [loading, setLoading] = useState(true);
  const [selectedGroupId, setSelectedGroupId] = useState<number | null>(null);
  const { openSnackbar } = useSnackbar();

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    loadAnniversaries();
  }, [selectedGroupId]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [annData, groupsData] = await Promise.all([
        getAnniversaries(),
        getGroups(),
      ]);
      setAnniversaries(annData);
      setOwnedGroups(groupsData.owned);
      setSubscribedGroups(groupsData.subscribed);
    } catch (err) {
      console.error("Failed to load:", err);
    } finally {
      setLoading(false);
    }
  };

  const loadAnniversaries = async () => {
    if (selectedGroupId === null) return;
    try {
      const data = await getAnniversaries(undefined, selectedGroupId || undefined);
      setAnniversaries(data);
    } catch (err) {
      console.error("Failed to load:", err);
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await deleteAnniversary(id);
      setAnniversaries(anniversaries.filter((a) => a.id !== id));
      openSnackbar({ text: "Đã xoá", type: "success" });
    } catch {
      openSnackbar({ text: "Lỗi khi xoá", type: "error" });
    }
  };

  const handleGroupFilter = async (groupId: number | null) => {
    setSelectedGroupId(groupId);
    try {
      const data = await getAnniversaries(undefined, groupId || undefined);
      setAnniversaries(data);
    } catch (err) {
      console.error("Failed to filter:", err);
    }
  };

  const allGroups = [...ownedGroups, ...subscribedGroups];

  // Group by lunar month
  const grouped = anniversaries.reduce<Record<number, Anniversary[]>>(
    (acc, a) => {
      if (!acc[a.lunar_month]) acc[a.lunar_month] = [];
      acc[a.lunar_month].push(a);
      return acc;
    },
    {}
  );

  const sortedMonths = Object.keys(grouped)
    .map(Number)
    .sort((a, b) => a - b);

  return (
    <Page className="pb-16 bg-gray-50">
      <Box className="p-4">
        <Box className="flex items-center justify-between mb-4">
          <Text.Title size="large">Danh sách giỗ</Text.Title>
          <Button
            variant="primary"
            size="small"
            onClick={() => navigate("/add")}
            prefixIcon={<Icon icon="zi-plus" />}
          >
            Thêm
          </Button>
        </Box>

        {/* Group filter chips */}
        {allGroups.length > 0 && (
          <Box className="flex space-x-2 mb-4 overflow-x-auto pb-1">
            <Button
              size="small"
              variant={selectedGroupId === null ? "primary" : "tertiary"}
              onClick={() => handleGroupFilter(null)}
              className="whitespace-nowrap"
            >
              Tất cả
            </Button>
            {allGroups.map((g) => (
              <Button
                key={g.id}
                size="small"
                variant={selectedGroupId === g.id ? "primary" : "tertiary"}
                onClick={() => handleGroupFilter(g.id)}
                className="whitespace-nowrap"
              >
                {g.name}
              </Button>
            ))}
          </Box>
        )}

        {loading ? (
          <Box className="flex justify-center py-12">
            <Spinner />
          </Box>
        ) : sortedMonths.length === 0 ? (
          <Box className="text-center py-12">
            <Text className="text-gray-400 text-lg mb-2">
              Chưa có ngày giỗ nào
            </Text>
            <Button
              variant="primary"
              onClick={() => navigate("/add")}
              prefixIcon={<Icon icon="zi-plus" />}
            >
              Thêm ngày giỗ đầu tiên
            </Button>
          </Box>
        ) : (
          sortedMonths.map((month) => (
            <Box key={month} className="mb-4">
              <Text size="small" className="font-semibold text-red-600 mb-2">
                {getLunarMonthName(month)} ({grouped[month].length})
              </Text>
              {grouped[month]
                .sort((a, b) => a.lunar_day - b.lunar_day)
                .map((a) => (
                  <AnniversaryCard
                    key={a.id}
                    anniversary={a}
                    onDelete={a.is_editable !== false ? handleDelete : undefined}
                  />
                ))}
            </Box>
          ))
        )}
      </Box>

      <BottomNav />
    </Page>
  );
}
