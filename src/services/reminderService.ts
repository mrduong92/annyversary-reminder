import api from "./api";
import type { Reminder, DefaultEvents } from "@/types";

export async function getUpcomingReminders(
  days: number = 30
): Promise<Reminder[]> {
  const { data } = await api.get<Reminder[]>("/reminders/upcoming", {
    params: { days },
  });
  return data;
}

export async function getDefaults(): Promise<DefaultEvents> {
  const { data } = await api.get<DefaultEvents>("/defaults");
  return data;
}

export async function updateDefaults(
  settings: Partial<DefaultEvents>
): Promise<DefaultEvents> {
  const { data } = await api.put<DefaultEvents>("/defaults", settings);
  return data;
}
