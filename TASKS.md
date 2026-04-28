# TASKS.md — Gia Phong

> - `[ ]` = chưa làm · `[x]` = done · `[-]` = in progress · `[~]` = defer · `⚠️` = blocked

---

## PHASE 0–3 ✅ DONE

- [x] Docker (Postgres 17 + pgvector + Nginx + PHP 8.3), Laravel 13, packages
- [x] Migrations: tất cả bảng + event_recipient pivot + family_groups + genealogy tables
- [x] Models, relationships, fillable, casts — MemorialEvent, FamilyMember, Recipient, etc.
- [x] `LunarCalendarService` — 11/11 test cases pass
- [x] Google OAuth (active) · OTP phone (built, bật khi cần)
- [x] EventController CRUD — âm/dương, date_type, family_member_id required
- [x] RecipientController CRUD — danh bạ độc lập, M2M pivot
- [x] PrayerController CRUD — AI soạn, lưu thủ công từ chat
- [x] Multi-family groups — switch/create/edit/delete, sidebar dropdown, Rằm/Mùng1 per-group
- [x] Dashboard — real data theo active group
- [x] Settings page
- [x] **Refactor core**: FamilyMember = source of truth, MemorialEvent chỉ lưu lịch nhắc, auto-sync qua booted() hook

---

## PHASE 4 — ZNS Notification

- [x] `ZnsService::send()` + log vào `notification_logs`
- [x] `SendZnsReminderJob` — queue, 3 retries
- [x] `ScheduleZnsReminders` — 7:00 sáng, BelongsToMany, notify_days_before per-link
- [x] `ScheduleLunarReminders` — 6:30 sáng, detect Rằm/Mùng1 âm lịch (gate: Premium)
- [x] `ResetMonthlyZnsCount` command (giờ đổi sang reset yearly)
- [x] Test end-to-end: dispatch → queue → Zalo API → `failed/Access token invalid` ✓
- ⚠️ **Blocked**: chờ Zalo OA verify + ZNS template duyệt (3–7 ngày)

---

## PHASE 5 — AI Agent ✅ DONE

- [x] Laravel AI SDK + Gemini 2.5 Flash — streaming, tool calling, conversation memory
- [x] `CalendarQueryTool`, `EventTool`, `PrayerTool`, `FamilyMemoryTool`, `GenealogyTool`
- [x] `PrayerSkill` + `CalendarSkill` — rules tách riêng file
- [x] Chat UI: streaming, Lưu văn khấn button, history reload, share panel
- [x] RAG pipeline: upload PDF/DOCX/TXT → chunk → embed (Gemini) → pgvector search
- [x] Bug fixes: Stringable casting, tool_results JSON key, conversation history format

---

## PHASE 6 — Share Chatbot ✅ DONE

- [x] 1 share link/group, toggle bật/tắt từ Chat AI header
- [x] Public chat `/s/{token}` — readOnly, per-member history, rate limit
- [x] Gate: Premium only (Gate `share-chat`)

---

## PHASE 7 — Export

- [ ] `ExportService::prayerToPdf(Prayer)` — dompdf
- [ ] `ExportService::eventsToTxt(group)` — plain text
- [ ] Nút "Tải PDF" trên `prayers/show`, nút "Xuất lịch giỗ" trên `events/index`

---

## PHASE 8 — Subscription ✅ DONE (MVP)

- [x] Spec: Free / Mini (100k/năm) / Premium (200k/năm) — theo `specs/subscriptions.md`
- [x] Migration: rename plans (basic→mini, unlimited→premium), ZNS counter đổi sang yearly
- [x] Bảng `subscriptions` — lưu lịch sử kích hoạt
- [x] `SubscriptionService` refactor — limits theo spec, yearly ZNS, feature gates
- [x] `php artisan subscription:activate {user} {plan}` — kích hoạt thủ công
- [x] Gates (Laravel Gate): `upload-document`, `share-chat`, `import-image`, `use-ram-mung-mot`, `generate-prayer`, `crud-events-ai`, `send-agent-message`
- [x] Pricing page `/upgrade` — 3 cột, hướng dẫn thanh toán chuyển khoản
- [x] Plan badge trong sidebar → link `/upgrade`
- [ ] **Freemium gate modal** — popup nâng cấp thay vì error message/redirect (nice-to-have)

---

## PHASE 9 (mới) — Gia Phả ✅ MOSTLY DONE

- [x] DB: `family_members` (name, pronoun, relationship, gender, birth/death, death_day/month) + `family_relationships` (parent_child, spouse)
- [x] FamilyMember = source of truth, auto-sync memorial_event khi save
- [x] `FamilyMemberController` — CRUD + couple-based parent selection
- [x] `FamilyTreeService` — roots, buildTree (max 10 levels), toText (for AI)
- [x] Tree view: `family-chart` v0.9 (bundled via Vite), CSS light theme override
- [x] List view: table với parents, ngày giỗ, edit link
- [x] `GenealogyTool` — AI agent có thể query gia phả
- [x] Import ngày giỗ từ ảnh — Gemini Vision → preview table → confirm (Premium gate)

---

## PHASE 10 — Polish & Launch

- [ ] **Landing page** `/` — giới thiệu sản phẩm + CTA (welcome.blade.php hiện là default)
- [ ] **Onboarding** — sau đăng ký lần đầu, hướng dẫn thêm thành viên + ngày giỗ đầu tiên
- [ ] **Error pages** 404 / 500 — branded
- [ ] **Responsive mobile** — test thực tế trên điện thoại
- [ ] **Deploy VPS** — Nginx + PHP 8.3 + Postgres + pgvector + supervisor + cron + SSL + Let's Encrypt
- [ ] **Freemium gate modal** — popup "Nâng cấp để dùng tính năng này" thay vì error message

---

## BACKLOG

- [~] OTP phone auth — bật khi có SpeedSMS credit (code đã có)
- [~] Payment gateway tự động (VNPay/MoMo) — manual chuyển khoản trước
- [~] Export PDF/Excel (Phase 7) — low priority trước launch
- [ ] Sinh nhật âm lịch
- [ ] Cáo phó, gia phả multi-generation export
- [ ] Admin dashboard

---

## Ghi chú kỹ thuật

- **DB**: PostgreSQL 17 + pgvector 0.8.2. Embedding: `gemini-embedding-001` dims=768 (outputDimensionality).
- **AI**: Laravel AI SDK 0.6.x + Gemini 2.5 Flash. Bug fix: `FamilyAgent::messages()` rebuild message format.
- **Subscription**: plans = free/mini/premium. ZNS tính theo năm. Gates đăng ký trong `AppServiceProvider`.
- **Genealogy**: `family_members.death_day/month` → auto-sync `memorial_events` qua `booted()` hook.
- **Tree**: `family-chart` v0.9 npm, CSS stroke override (white → gray), container cần class `f3`.
- **Auth**: Google OAuth active. OTP built, flip `AUTH_DRIVER=otp` khi cần.
- **ZNS**: Code đủ, blocked Zalo credentials. Submit OA + template ngay.
