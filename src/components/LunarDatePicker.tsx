import { Box, Select, Text } from "zmp-ui";

const { Option } = Select;

interface Props {
  day: number;
  month: number;
  onDayChange: (day: number) => void;
  onMonthChange: (month: number) => void;
  label?: string;
}

const MONTH_NAMES = [
  "Tháng Giêng",
  "Tháng Hai",
  "Tháng Ba",
  "Tháng Tư",
  "Tháng Năm",
  "Tháng Sáu",
  "Tháng Bảy",
  "Tháng Tám",
  "Tháng Chín",
  "Tháng Mười",
  "Tháng Mười Một",
  "Tháng Chạp",
];

export default function LunarDatePicker({
  day,
  month,
  onDayChange,
  onMonthChange,
  label = "Ngày âm lịch",
}: Props) {
  return (
    <Box className="space-y-2">
      {label && (
        <Text size="small" className="font-medium">
          {label}
        </Text>
      )}
      <Box className="flex space-x-3">
        <Box className="flex-1">
          <Select
            label="Ngày"
            value={String(day)}
            onChange={(value) => onDayChange(Number(value))}
          >
            {Array.from({ length: 30 }, (_, i) => i + 1).map((d) => (
              <Option key={d} value={String(d)} title={`Ngày ${d}`} />
            ))}
          </Select>
        </Box>
        <Box className="flex-1">
          <Select
            label="Tháng"
            value={String(month)}
            onChange={(value) => onMonthChange(Number(value))}
          >
            {MONTH_NAMES.map((name, i) => (
              <Option
                key={i + 1}
                value={String(i + 1)}
                title={name}
              />
            ))}
          </Select>
        </Box>
      </Box>
    </Box>
  );
}
