/**
 * Utility formatters for dates and display.
 */

export function formatSolarDate(dateStr: string): string {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return `${d.getDate().toString().padStart(2, "0")}/${(d.getMonth() + 1)
    .toString()
    .padStart(2, "0")}/${d.getFullYear()}`;
}

export function daysUntil(dateStr: string): number {
  const target = new Date(dateStr);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  target.setHours(0, 0, 0, 0);
  return Math.ceil((target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
}

export function getDaysUntilLabel(dateStr: string): string {
  const days = daysUntil(dateStr);
  if (days === 0) return "Hôm nay";
  if (days === 1) return "Ngày mai";
  if (days < 0) return `${Math.abs(days)} ngày trước`;
  return `Còn ${days} ngày`;
}

export function getRelationshipIcon(relationship: string | null): string {
  if (!relationship) return "👤";
  const r = relationship.toLowerCase();
  if (r.includes("ông")) return "👴";
  if (r.includes("bà")) return "👵";
  if (r.includes("cha") || r.includes("bố") || r.includes("ba")) return "👨";
  if (r.includes("mẹ") || r.includes("má")) return "👩";
  if (r.includes("anh") || r.includes("chú") || r.includes("bác")) return "👨";
  if (r.includes("chị") || r.includes("cô") || r.includes("dì")) return "👩";
  return "👤";
}
