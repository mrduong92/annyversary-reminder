import { atom } from "jotai";
import type {
  User,
  Anniversary,
  AnniversaryGroup,
  OcrResult,
  ChatMessage,
  DefaultEvents,
  Reminder,
} from "@/types";

export const userAtom = atom<User | null>(null);
export const tokenAtom = atom<string | null>(null);
export const anniversariesAtom = atom<Anniversary[]>([]);
export const ocrResultAtom = atom<OcrResult[]>([]);
export const chatMessagesAtom = atom<ChatMessage[]>([]);
export const defaultEventsAtom = atom<DefaultEvents>({
  ram_15: false,
  mung_1: false,
});
export const remindersAtom = atom<Reminder[]>([]);
export const loadingAtom = atom<boolean>(false);

export const ownedGroupsAtom = atom<AnniversaryGroup[]>([]);
export const subscribedGroupsAtom = atom<(AnniversaryGroup & { owner_name: string; owner_avatar: string })[]>([]);
export const selectedGroupIdAtom = atom<number | null>(null);
