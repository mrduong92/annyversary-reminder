import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { useAtom } from "jotai";
import {
  Box,
  Button,
  Icon,
  Input,
  Modal,
  Page,
  Text,
  Spinner,
  useSnackbar,
} from "zmp-ui";
import { userAtom } from "@/store/atoms";
import {
  getGroups,
  renameGroup,
  deleteGroup,
  unsubscribe,
} from "@/services/groupService";
import { getAnniversaries, deleteAnniversary } from "@/services/anniversaryService";
import AnniversaryCard from "@/components/AnniversaryCard";
import ShareSheet from "@/components/ShareSheet";
import SubscriberList from "@/components/SubscriberList";
import BottomNav from "@/components/BottomNav";
import { getLunarMonthName } from "@/utils/lunarCalendar";
import type { AnniversaryGroup, Anniversary } from "@/types";

export default function GroupDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [user] = useAtom(userAtom);
  const { openSnackbar } = useSnackbar();

  const [group, setGroup] = useState<AnniversaryGroup | null>(null);
  const [anniversaries, setAnniversaries] = useState<Anniversary[]>([]);
  const [loading, setLoading] = useState(true);
  const [showShare, setShowShare] = useState(false);
  const [showRename, setShowRename] = useState(false);
  const [renameValue, setRenameValue] = useState("");
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const groupId = Number(id);
  const isOwner = group?.user_id === user?.id;

  useEffect(() => {
    loadData();
  }, [id]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [groupsData, annData] = await Promise.all([
        getGroups(),
        getAnniversaries(undefined, groupId),
      ]);

      const allGroups = [...groupsData.owned, ...groupsData.subscribed];
      const found = allGroups.find((g) => g.id === groupId);
      setGroup(found || null);
      setAnniversaries(annData);
    } catch (err) {
      console.error("Failed to load:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleRename = async () => {
    if (!renameValue.trim() || !group) return;
    try {
      const updated = await renameGroup(group.id, renameValue.trim());
      setGroup({ ...group, ...updated });
      setShowRename(false);
      openSnackbar({ text: "Đã đổi tên", type: "success" });
    } catch {
      openSnackbar({ text: "Lỗi khi đổi tên", type: "error" });
    }
  };

  const handleDelete = async () => {
    if (!group) return;
    try {
      await deleteGroup(group.id);
      openSnackbar({ text: "Đã xoá nhóm", type: "success" });
      navigate("/groups");
    } catch {
      openSnackbar({ text: "Lỗi khi xoá nhóm", type: "error" });
    }
  };

  const handleUnsubscribe = async () => {
    if (!group) return;
    try {
      await unsubscribe(group.id);
      openSnackbar({ text: "Đã rời nhóm", type: "success" });
      navigate("/groups");
    } catch {
      openSnackbar({ text: "Lỗi khi rời nhóm", type: "error" });
    }
  };

  const handleDeleteAnniversary = async (annId: number) => {
    try {
      await deleteAnniversary(annId);
      setAnniversaries((prev) => prev.filter((a) => a.id !== annId));
      openSnackbar({ text: "Đã xoá", type: "success" });
    } catch {
      openSnackbar({ text: "Lỗi khi xoá", type: "error" });
    }
  };

  // Group by lunar month
  const grouped = anniversaries.reduce<Record<number, Anniversary[]>>(
    (acc, a) => {
      if (!acc[a.lunar_month]) acc[a.lunar_month] = [];
      acc[a.lunar_month].push(a);
      return acc;
    },
    {}
  );
  const sortedMonths = Object.keys(grouped).map(Number).sort((a, b) => a - b);

  if (loading) {
    return (
      <Page className="pb-16 bg-gray-50">
        <Box className="flex justify-center py-12">
          <Spinner />
        </Box>
        <BottomNav />
      </Page>
    );
  }

  if (!group) {
    return (
      <Page className="pb-16 bg-gray-50">
        <Box className="text-center py-12">
          <Text className="text-gray-400">Không tìm thấy nhóm</Text>
        </Box>
        <BottomNav />
      </Page>
    );
  }

  return (
    <Page className="pb-16 bg-gray-50">
      <Box className="p-4">
        {/* Header */}
        <Box className="flex items-center justify-between mb-4">
          <Box className="flex items-center space-x-2">
            <Button
              variant="tertiary"
              size="small"
              onClick={() => navigate("/groups")}
            >
              <Icon icon="zi-arrow-left" />
            </Button>
            <Text.Title size="large">{group.name}</Text.Title>
          </Box>
          {isOwner && (
            <Box className="flex items-center space-x-2">
              <Button
                variant="secondary"
                size="small"
                onClick={() => setShowShare(true)}
                prefixIcon={<Icon icon="zi-share" />}
              >
                Chia sẻ
              </Button>
              <Button
                variant="secondary"
                size="small"
                onClick={() => {
                  setRenameValue(group.name);
                  setShowRename(true);
                }}
              >
                <Icon icon="zi-edit" />
              </Button>
            </Box>
          )}
        </Box>

        {/* Owner actions */}
        {isOwner && (
          <Box className="mb-4">
            <Button
              variant="primary"
              size="small"
              onClick={() => navigate(`/add?group_id=${group.id}`)}
              prefixIcon={<Icon icon="zi-plus" />}
            >
              Thêm ngày giỗ
            </Button>
          </Box>
        )}

        {/* Subscriber action */}
        {!isOwner && (
          <Box className="mb-4">
            <Button
              variant="tertiary"
              size="small"
              onClick={handleUnsubscribe}
              className="text-red-600"
            >
              Rời nhóm
            </Button>
          </Box>
        )}

        {/* Anniversary list */}
        {sortedMonths.length === 0 ? (
          <Box className="text-center py-8 bg-white rounded-lg">
            <Text className="text-gray-400">Chưa có ngày giỗ nào</Text>
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
                    onDelete={
                      a.is_editable !== false
                        ? handleDeleteAnniversary
                        : undefined
                    }
                  />
                ))}
            </Box>
          ))
        )}

        {/* Subscribers section (owner only) */}
        {isOwner && (
          <Box className="mt-6">
            <SubscriberList groupId={group.id} />
          </Box>
        )}

        {/* Delete group (owner only, not default) */}
        {isOwner && !group.is_default && (
          <Box className="mt-6">
            <Button
              fullWidth
              variant="tertiary"
              onClick={() => setShowDeleteConfirm(true)}
              className="text-red-600"
            >
              Xoá nhóm
            </Button>
          </Box>
        )}
      </Box>

      {/* Share sheet */}
      {group && (
        <ShareSheet
          group={group}
          visible={showShare}
          onClose={() => setShowShare(false)}
          onUpdate={(updated) => setGroup(updated)}
        />
      )}

      {/* Rename modal */}
      <Modal
        visible={showRename}
        onClose={() => setShowRename(false)}
        title="Đổi tên nhóm"
        actions={[
          { text: "Huỷ", close: true },
          {
            text: "Lưu",
            highLight: true,
            onClick: handleRename,
          },
        ]}
      >
        <Box className="p-4">
          <Input
            value={renameValue}
            onChange={(e) => setRenameValue(e.target.value)}
          />
        </Box>
      </Modal>

      {/* Delete confirmation */}
      <Modal
        visible={showDeleteConfirm}
        onClose={() => setShowDeleteConfirm(false)}
        title="Xoá nhóm"
        description="Các ngày giỗ sẽ được chuyển về nhóm Chung. Thành viên sẽ mất quyền truy cập."
        actions={[
          { text: "Huỷ", close: true },
          {
            text: "Xoá",
            highLight: true,
            onClick: handleDelete,
          },
        ]}
      />

      <BottomNav />
    </Page>
  );
}
