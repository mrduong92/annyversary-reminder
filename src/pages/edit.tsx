import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { useAtom } from "jotai";
import { Box, Page, Text, Spinner, useSnackbar } from "zmp-ui";
import { anniversariesAtom } from "@/store/atoms";
import {
  getAnniversaries,
  updateAnniversary,
} from "@/services/anniversaryService";
import AnniversaryForm from "@/components/AnniversaryForm";
import BottomNav from "@/components/BottomNav";
import type { AnniversaryInput } from "@/types";

export default function EditAnniversaryPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [anniversaries, setAnniversaries] = useAtom(anniversariesAtom);
  const [loading, setLoading] = useState(false);
  const { openSnackbar } = useSnackbar();

  const anniversary = anniversaries.find((a) => a.id === Number(id));

  useEffect(() => {
    if (anniversaries.length === 0) {
      getAnniversaries().then(setAnniversaries);
    }
  }, []);

  if (!anniversary) {
    return (
      <Page className="pb-16 bg-gray-50">
        <Box className="flex justify-center py-12">
          <Spinner />
        </Box>
        <BottomNav />
      </Page>
    );
  }

  const handleSubmit = async (values: AnniversaryInput) => {
    try {
      setLoading(true);
      const updated = await updateAnniversary(Number(id), values);
      setAnniversaries((prev) =>
        prev.map((a) => (a.id === updated.id ? updated : a))
      );
      openSnackbar({ text: "Đã cập nhật", type: "success" });
      navigate("/list");
    } catch (err) {
      openSnackbar({ text: "Lỗi khi cập nhật", type: "error" });
    } finally {
      setLoading(false);
    }
  };

  return (
    <Page className="pb-16 bg-gray-50">
      <Box className="p-4">
        <Text.Title size="large" className="mb-4">
          Sửa ngày giỗ
        </Text.Title>
        <AnniversaryForm
          initialValues={{
            person_name: anniversary.person_name,
            relationship: anniversary.relationship || undefined,
            lunar_day: anniversary.lunar_day,
            lunar_month: anniversary.lunar_month,
            lunar_year: anniversary.lunar_year || undefined,
            notes: anniversary.notes || undefined,
          }}
          onSubmit={handleSubmit}
          loading={loading}
          submitLabel="Cập nhật"
        />
      </Box>
      <BottomNav />
    </Page>
  );
}
