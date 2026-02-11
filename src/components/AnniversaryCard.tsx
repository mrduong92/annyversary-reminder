import { Box, Text, Button, Icon } from "zmp-ui";
import type { Anniversary } from "@/types";
import { formatSolarDate, getDaysUntilLabel, getRelationshipIcon } from "@/utils/formatters";
import { formatLunarDate } from "@/utils/lunarCalendar";
import { useNavigate } from "react-router-dom";

interface Props {
  anniversary: Anniversary;
  onDelete?: (id: number) => void;
}

export default function AnniversaryCard({ anniversary, onDelete }: Props) {
  const navigate = useNavigate();
  const a = anniversary;
  const isEditable = a.is_editable !== false;

  return (
    <Box
      className="bg-white rounded-lg p-4 mb-3 shadow-sm border border-gray-100"
      onClick={() => isEditable && navigate(`/edit/${a.id}`)}
    >
      <Box className="flex items-start justify-between">
        <Box className="flex items-center space-x-3">
          <Box className="text-2xl">{getRelationshipIcon(a.relationship)}</Box>
          <Box>
            <Text.Title size="small" className="font-semibold">
              {a.person_name}
            </Text.Title>
            {a.relationship && (
              <Text size="xSmall" className="text-gray-500">
                {a.relationship}
              </Text>
            )}
            {a.group_name && (
              <Box className="inline-block bg-gray-100 rounded px-1.5 py-0.5 mt-0.5">
                <Text size="xxSmall" className="text-gray-500">
                  {a.group_name}
                </Text>
              </Box>
            )}
          </Box>
        </Box>
        <Box className="text-right">
          <Text size="small" className="font-medium text-red-600">
            {formatLunarDate(a.lunar_day, a.lunar_month)}
          </Text>
          {a.solar_date && (
            <>
              <Text size="xSmall" className="text-gray-500">
                {formatSolarDate(a.solar_date)}
              </Text>
              <Text size="xSmall" className="text-blue-600 font-medium">
                {getDaysUntilLabel(a.solar_date)}
              </Text>
            </>
          )}
        </Box>
      </Box>
      {a.notes && (
        <Text size="xSmall" className="text-gray-400 mt-2">
          {a.notes}
        </Text>
      )}
      {onDelete && isEditable && (
        <Box className="flex justify-end mt-2">
          <Button
            size="small"
            variant="tertiary"
            onClick={(e) => {
              e.stopPropagation();
              onDelete(a.id);
            }}
          >
            <Icon icon="zi-delete" />
          </Button>
        </Box>
      )}
    </Box>
  );
}
