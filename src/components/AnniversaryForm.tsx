import { useState, useEffect } from "react";
import { useAtom } from "jotai";
import { Box, Button, Input, Select, Text } from "zmp-ui";
import { ownedGroupsAtom } from "@/store/atoms";
import { getGroups } from "@/services/groupService";
import type { AnniversaryInput } from "@/types";

const { Option } = Select;

const RELATIONSHIPS = [
  "Ông nội",
  "Bà nội",
  "Ông ngoại",
  "Bà ngoại",
  "Cha/Bố",
  "Mẹ/Má",
  "Anh",
  "Chị",
  "Em",
  "Chú",
  "Bác",
  "Cô",
  "Dì",
  "Cậu",
  "Mợ",
  "Khác",
];

interface Props {
  initialValues?: Partial<AnniversaryInput>;
  onSubmit: (values: AnniversaryInput) => void;
  loading?: boolean;
  submitLabel?: string;
  defaultGroupId?: number;
}

export default function AnniversaryForm({
  initialValues,
  onSubmit,
  loading = false,
  submitLabel = "Lưu",
  defaultGroupId,
}: Props) {
  const [ownedGroups, setOwnedGroups] = useAtom(ownedGroupsAtom);
  const [personName, setPersonName] = useState(
    initialValues?.person_name || ""
  );
  const [relationship, setRelationship] = useState(
    initialValues?.relationship || ""
  );
  const [lunarDay, setLunarDay] = useState(
    initialValues?.lunar_day?.toString() || ""
  );
  const [lunarMonth, setLunarMonth] = useState(
    initialValues?.lunar_month?.toString() || ""
  );
  const [lunarYear, setLunarYear] = useState(
    initialValues?.lunar_year?.toString() || ""
  );
  const [notes, setNotes] = useState(initialValues?.notes || "");
  const [groupId, setGroupId] = useState(
    initialValues?.group_id?.toString() || defaultGroupId?.toString() || ""
  );

  useEffect(() => {
    if (ownedGroups.length === 0) {
      getGroups()
        .then((data) => setOwnedGroups(data.owned))
        .catch(() => {});
    }
  }, []);

  const handleSubmit = () => {
    if (!personName || !lunarDay || !lunarMonth) return;

    const day = parseInt(lunarDay, 10);
    const month = parseInt(lunarMonth, 10);
    if (day < 1 || day > 30 || month < 1 || month > 12) return;

    onSubmit({
      person_name: personName,
      relationship: relationship || undefined,
      lunar_day: day,
      lunar_month: month,
      lunar_year: lunarYear ? parseInt(lunarYear, 10) : undefined,
      notes: notes || undefined,
      group_id: groupId ? parseInt(groupId, 10) : undefined,
    });
  };

  return (
    <Box className="space-y-4">
      <Box>
        <Text size="small" className="mb-1 font-medium">
          Tên người mất *
        </Text>
        <Input
          placeholder="VD: Nguyễn Văn A"
          value={personName}
          onChange={(e) => setPersonName(e.target.value)}
        />
      </Box>

      <Box>
        <Text size="small" className="mb-1 font-medium">
          Quan hệ
        </Text>
        <Select
          placeholder="Chọn quan hệ"
          value={relationship}
          onChange={(value) => setRelationship(value as string)}
        >
          {RELATIONSHIPS.map((r) => (
            <Option key={r} value={r} title={r} />
          ))}
        </Select>
      </Box>

      {ownedGroups.length > 0 && (
        <Box>
          <Text size="small" className="mb-1 font-medium">
            Nhóm
          </Text>
          <Select
            placeholder="Chọn nhóm"
            value={groupId}
            onChange={(value) => setGroupId(value as string)}
          >
            {ownedGroups.map((g) => (
              <Option key={g.id} value={String(g.id)} title={g.name} />
            ))}
          </Select>
        </Box>
      )}

      <Box className="flex space-x-3">
        <Box className="flex-1">
          <Text size="small" className="mb-1 font-medium">
            Ngày âm *
          </Text>
          <Input
            type="number"
            placeholder="1-30"
            value={lunarDay}
            onChange={(e) => setLunarDay(e.target.value)}
          />
        </Box>
        <Box className="flex-1">
          <Text size="small" className="mb-1 font-medium">
            Tháng âm *
          </Text>
          <Input
            type="number"
            placeholder="1-12"
            value={lunarMonth}
            onChange={(e) => setLunarMonth(e.target.value)}
          />
        </Box>
        <Box className="flex-1">
          <Text size="small" className="mb-1 font-medium">
            Năm âm
          </Text>
          <Input
            type="number"
            placeholder="VD: 2020"
            value={lunarYear}
            onChange={(e) => setLunarYear(e.target.value)}
          />
        </Box>
      </Box>

      <Box>
        <Text size="small" className="mb-1 font-medium">
          Ghi chú
        </Text>
        <Input.TextArea
          placeholder="Ghi chú thêm..."
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
        />
      </Box>

      <Button
        fullWidth
        variant="primary"
        onClick={handleSubmit}
        loading={loading}
        disabled={!personName || !lunarDay || !lunarMonth}
      >
        {submitLabel}
      </Button>
    </Box>
  );
}
