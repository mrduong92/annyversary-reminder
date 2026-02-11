import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { Box, Button, Page, Text, Spinner, useSnackbar } from "zmp-ui";
import { previewGroup, joinGroup } from "@/services/groupService";
import type { GroupPreview } from "@/types";

export default function JoinPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const code = searchParams.get("code") || "";
  const { openSnackbar } = useSnackbar();

  const [preview, setPreview] = useState<GroupPreview | null>(null);
  const [loading, setLoading] = useState(true);
  const [joining, setJoining] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (code) loadPreview();
    else setError("Không có mã chia sẻ");
  }, [code]);

  const loadPreview = async () => {
    try {
      setLoading(true);
      const data = await previewGroup(code);
      setPreview(data);
    } catch (err: any) {
      if (err.response?.status === 404) {
        setError("Nhóm không tồn tại hoặc đã hết hạn chia sẻ");
      } else {
        setError("Không thể tải thông tin nhóm");
      }
    } finally {
      setLoading(false);
    }
  };

  const handleJoin = async () => {
    try {
      setJoining(true);
      const result = await joinGroup(code);
      openSnackbar({ text: "Đã tham gia nhóm!", type: "success" });
      navigate(`/group/${result.group.id}`);
    } catch (err: any) {
      if (err.response?.status === 409) {
        openSnackbar({ text: "Đã tham gia nhóm này rồi", type: "warning" });
        navigate("/groups");
      } else if (err.response?.status === 400) {
        openSnackbar({
          text: "Không thể tham gia nhóm của chính mình",
          type: "error",
        });
      } else {
        openSnackbar({ text: "Lỗi khi tham gia", type: "error" });
      }
    } finally {
      setJoining(false);
    }
  };

  return (
    <Page className="bg-gray-50 min-h-screen">
      <Box className="p-6 flex flex-col items-center justify-center min-h-screen">
        {loading ? (
          <Spinner />
        ) : error ? (
          <Box className="text-center">
            <Text className="text-gray-400 text-lg mb-4">{error}</Text>
            <Button variant="primary" onClick={() => navigate("/")}>
              Về trang chủ
            </Button>
          </Box>
        ) : preview ? (
          <Box className="bg-white rounded-2xl p-6 w-full max-w-sm shadow-lg text-center">
            {/* Owner avatar */}
            {preview.owner_avatar ? (
              <img
                src={preview.owner_avatar}
                alt=""
                className="w-16 h-16 rounded-full mx-auto mb-3"
              />
            ) : (
              <Box className="w-16 h-16 rounded-full bg-red-100 mx-auto mb-3 flex items-center justify-center">
                <Text className="text-2xl">👨‍👩‍👧‍👦</Text>
              </Box>
            )}

            <Text.Title size="normal" className="font-bold">
              {preview.name}
            </Text.Title>

            <Text size="small" className="text-gray-500 mt-1">
              Được tạo bởi {preview.owner_name || "Người dùng"}
            </Text>

            <Box className="flex justify-center space-x-6 mt-4 mb-6">
              <Box className="text-center">
                <Text className="font-bold text-lg">
                  {preview.anniversary_count}
                </Text>
                <Text size="xSmall" className="text-gray-500">
                  Ngày giỗ
                </Text>
              </Box>
              <Box className="text-center">
                <Text className="font-bold text-lg">
                  {preview.subscriber_count}
                </Text>
                <Text size="xSmall" className="text-gray-500">
                  Thành viên
                </Text>
              </Box>
            </Box>

            <Button
              fullWidth
              variant="primary"
              onClick={handleJoin}
              loading={joining}
            >
              Đồng ý tham gia
            </Button>

            <Button
              fullWidth
              variant="tertiary"
              className="mt-2"
              onClick={() => navigate("/")}
            >
              Bỏ qua
            </Button>
          </Box>
        ) : null}
      </Box>
    </Page>
  );
}
