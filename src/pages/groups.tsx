import { useEffect, useState } from "react";
import { useAtom } from "jotai";
import { useNavigate } from "react-router-dom";
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
import { ownedGroupsAtom, subscribedGroupsAtom } from "@/store/atoms";
import { getGroups, createGroup } from "@/services/groupService";
import GroupCard from "@/components/GroupCard";
import BottomNav from "@/components/BottomNav";

export default function GroupsPage() {
  const navigate = useNavigate();
  const [ownedGroups, setOwnedGroups] = useAtom(ownedGroupsAtom);
  const [subscribedGroups, setSubscribedGroups] = useAtom(subscribedGroupsAtom);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [newName, setNewName] = useState("");
  const [creating, setCreating] = useState(false);
  const { openSnackbar } = useSnackbar();

  useEffect(() => {
    loadGroups();
  }, []);

  const loadGroups = async () => {
    try {
      setLoading(true);
      const data = await getGroups();
      setOwnedGroups(data.owned);
      setSubscribedGroups(data.subscribed);
    } catch (err) {
      console.error("Failed to load groups:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = async () => {
    if (!newName.trim()) return;
    try {
      setCreating(true);
      const group = await createGroup(newName.trim());
      setOwnedGroups((prev) => [...prev, group]);
      setNewName("");
      setShowCreate(false);
      openSnackbar({ text: "Đã tạo nhóm", type: "success" });
    } catch (err) {
      openSnackbar({ text: "Lỗi khi tạo nhóm", type: "error" });
    } finally {
      setCreating(false);
    }
  };

  return (
    <Page className="pb-16 bg-gray-50">
      <Box className="p-4">
        <Box className="flex items-center justify-between mb-4">
          <Text.Title size="large">Nhóm giỗ</Text.Title>
          <Button
            variant="primary"
            size="small"
            onClick={() => setShowCreate(true)}
            prefixIcon={<Icon icon="zi-plus" />}
          >
            Tạo nhóm
          </Button>
        </Box>

        {loading ? (
          <Box className="flex justify-center py-12">
            <Spinner />
          </Box>
        ) : (
          <>
            {/* Owned groups */}
            {ownedGroups.length > 0 && (
              <Box className="mb-4">
                <Text size="small" className="font-semibold text-gray-600 mb-2">
                  Nhóm của tôi
                </Text>
                {ownedGroups.map((g) => (
                  <GroupCard
                    key={g.id}
                    group={g}
                    isOwned
                    onClick={() => navigate(`/group/${g.id}`)}
                  />
                ))}
              </Box>
            )}

            {/* Subscribed groups */}
            {subscribedGroups.length > 0 && (
              <Box>
                <Text size="small" className="font-semibold text-gray-600 mb-2">
                  Nhóm đã tham gia
                </Text>
                {subscribedGroups.map((g) => (
                  <GroupCard
                    key={g.id}
                    group={g}
                    isOwned={false}
                    onClick={() => navigate(`/group/${g.id}`)}
                  />
                ))}
              </Box>
            )}

            {ownedGroups.length === 0 && subscribedGroups.length === 0 && (
              <Box className="text-center py-12">
                <Text className="text-gray-400 text-lg mb-2">
                  Chưa có nhóm nào
                </Text>
                <Button
                  variant="primary"
                  onClick={() => setShowCreate(true)}
                  prefixIcon={<Icon icon="zi-plus" />}
                >
                  Tạo nhóm đầu tiên
                </Button>
              </Box>
            )}
          </>
        )}
      </Box>

      {/* Create group modal */}
      <Modal
        visible={showCreate}
        onClose={() => setShowCreate(false)}
        title="Tạo nhóm mới"
        actions={[
          {
            text: "Huỷ",
            close: true,
          },
          {
            text: "Tạo",
            highLight: true,
            onClick: handleCreate,
            disabled: !newName.trim() || creating,
          },
        ]}
      >
        <Box className="p-4">
          <Input
            placeholder="Tên nhóm (VD: Giỗ bên Nội)"
            value={newName}
            onChange={(e) => setNewName(e.target.value)}
          />
        </Box>
      </Modal>

      <BottomNav />
    </Page>
  );
}
