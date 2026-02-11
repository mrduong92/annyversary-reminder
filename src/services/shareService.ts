import { openShareSheet } from "zmp-sdk";

const APP_ID = (window as any).APP_CONFIG?.app?.appId || "";

export function buildShareLink(shareCode: string): string {
  return `https://zalo.me/s/${APP_ID}/?share_code=${shareCode}`;
}

export async function shareViaZalo(
  shareCode: string,
  groupName: string
): Promise<void> {
  const link = buildShareLink(shareCode);

  try {
    await openShareSheet({
      type: "link",
      data: {
        link,
        title: `Nhóm giỗ: ${groupName}`,
        description: "Nhấn để tham gia nhóm nhắc giỗ trên Nhắc Lịch Giỗ",
      },
    });
  } catch (err) {
    console.error("Share failed:", err);
    throw err;
  }
}
