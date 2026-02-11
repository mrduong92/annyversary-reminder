export interface User {
  id: number;
  zalo_uid: string;
  display_name: string | null;
  avatar_url: string | null;
  created_at: string;
}

export interface Anniversary {
  id: number;
  user_id: number;
  person_name: string;
  relationship: string | null;
  lunar_day: number;
  lunar_month: number;
  lunar_year: number | null;
  notes: string | null;
  is_recurring: boolean;
  source: "manual" | "ocr";
  solar_date?: string | null;
  group_id: number | null;
  group_name: string | null;
  is_editable?: boolean;
  created_at: string;
  updated_at: string;
}

export interface AnniversaryInput {
  person_name: string;
  relationship?: string;
  lunar_day: number;
  lunar_month: number;
  lunar_year?: number;
  notes?: string;
  is_recurring?: boolean;
  source?: "manual" | "ocr";
  group_id?: number;
}

export interface OcrResult {
  person_name: string;
  relationship: string;
  lunar_day: number;
  lunar_month: number;
  notes: string;
}

export interface ChatMessage {
  id: number;
  user_id: number;
  role: "user" | "assistant";
  content: string;
  created_at: string;
}

export interface Reminder {
  id: number;
  user_id: number;
  anniversary_id: number | null;
  event_type: string | null;
  solar_date: string;
  remind_date: string;
  days_before: number;
  status: string;
  sent_at: string | null;
  person_name?: string;
  relationship?: string;
  lunar_day?: number;
  lunar_month?: number;
}

export interface DefaultEvents {
  ram_15: boolean;
  mung_1: boolean;
}

export interface LunarDate {
  day: number;
  month: number;
  year: number;
  isLeap?: boolean;
}

export interface AnniversaryGroup {
  id: number;
  user_id: number;
  name: string;
  share_code: string | null;
  is_default: boolean;
  anniversary_count: number;
  subscriber_count: number;
  owner_name?: string;
  owner_avatar?: string;
  created_at: string;
  updated_at: string;
}

export interface GroupSubscription {
  id: number;
  group_id: number;
  user_id: number;
  created_at: string;
}

export interface GroupsResponse {
  owned: AnniversaryGroup[];
  subscribed: (AnniversaryGroup & { owner_name: string; owner_avatar: string })[];
}

export interface GroupPreview {
  id: number;
  name: string;
  anniversary_count: number;
  subscriber_count: number;
  owner_name: string | null;
  owner_avatar: string | null;
}

export interface Subscriber {
  user_id: number;
  display_name: string | null;
  avatar_url: string | null;
  subscribed_at: string;
}
