import { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { useAtom } from "jotai";
import { Box, Page, Text, useSnackbar } from "zmp-ui";
import { anniversariesAtom } from "@/store/atoms";
import { createAnniversary } from "@/services/anniversaryService";
import AnniversaryForm from "@/components/AnniversaryForm";
import BottomNav from "@/components/BottomNav";
import type { AnniversaryInput } from "@/types";

export default function AddAnniversaryPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const defaultGroupId = searchParams.get("group_id")
    ? Number(searchParams.get("group_id"))
    : undefined;
  const [, setAnniversaries] = useAtom(anniversariesAtom);
  const [loading, setLoading] = useState(false);
  const { openSnackbar } = useSnackbar();

  const handleSubmit = async (values: AnniversaryInput) => {
    try {
      setLoading(true);
      const created = await createAnniversary(values);
      setAnniversaries((prev) => [...prev, created]);
      openSnackbar({ text: "Đã thêm ngày giỗ", type: "success" });
      if (defaultGroupId) {
        navigate(`/group/${defaultGroupId}`);
      } else {
        navigate("/list");
      }
    } catch (err) {
      openSnackbar({ text: "Lỗi khi thêm", type: "error" });
    } finally {
      setLoading(false);
    }
  };

  return (
    <Page className="pb-16 bg-gray-50">
      <Box className="p-4">
        <Text.Title size="large" className="mb-4">
          Thêm ngày giỗ
        </Text.Title>
        <AnniversaryForm
          onSubmit={handleSubmit}
          loading={loading}
          submitLabel="Thêm ngày giỗ"
          defaultGroupId={defaultGroupId}
        />
      </Box>
      <BottomNav />
    </Page>
  );
}
