# TASKS.md — Gia Phong

> Hướng dẫn dùng file này:
> - `[ ]` = chưa làm
> - `[x]` = done
> - `[-]` = đang làm / in progress
> - `[~]` = skip / defer sang phase sau
> Claude Code đọc file này để biết context, cập nhật status sau mỗi task hoàn thành.

---

## PHASE 0 — Setup & Init ✅

- [x] Docker Compose (app + nginx + mysql + queue + scheduler)
- [x] Laravel 12 init, `.env` config đầy đủ
- [x] Packages: breeze, socialite, dompdf, excel, lunar-calendar, flowbite

---

## PHASE 1 — Foundation ✅

- [x] Migrations: tất cả bảng (users, memorial_events, recipients, event_recipient pivot, notification_logs, prayers, family_shares, agent_messages, family_documents, document_chunks)
- [x] Models: đầy đủ fillable, casts, relationships (MemorialEvent ↔ Recipient là BelongsToMany qua event_recipient)
- [x] `LunarCalendarService`: lunarToSolar, nextOccurrence, nextSolarOccurrence, daysUntil — 11/11 test cases pass
- [x] `SubscriptionService`: kiểm tra giới hạn freemium cho tất cả features

---

## PHASE 2 — Auth ✅

- [x] Google OAuth (`GoogleController`) — đang dùng, test thành công
- [x] OTP phone (`OtpController` + `SmsOtpService`) — built, bật khi có SpeedSMS credit
- [x] `config/common.php` — switch `AUTH_DRIVER=google|otp`

---

## PHASE 3 — Quản lý Lịch Giỗ ✅

### EventController
- [x] index, create, store, edit, update, destroy
- [x] show — chi tiết event + attach/detach recipients từ show page
- [x] `attachRecipient` / `detachRecipient` — quản lý fine-grained từ show page
- [x] Form create/edit có section chọn recipients (checkbox) luôn
- [x] Support âm lịch + dương lịch (`date_type` enum)
- [x] Freemium gate: ≤ 3 events (free)

### RecipientController
- [x] index, create, store, edit, update, destroy (danh bạ độc lập)
- [x] Validate SĐT Việt Nam
- [x] Freemium gate: ≤ 2 recipients (free)

### Pivot event_recipient
- [x] `notify_days_before` per-link (override recipient default)
- [x] BelongsToMany với withPivot

### Views
- [x] Flowbite layout (sidebar, mobile responsive, flash messages)
- [x] `events/index` — table, badge âm/dương, days_until, link "Quản lý người nhận"
- [x] `events/create` + `events/edit` — form với recipient checkbox section
- [x] `events/show` — chi tiết + attach/detach từ danh bạ
- [x] `recipients/index` + `create` + `edit` — CRUD danh bạ
- [x] Dashboard (stats hardcode, cần update thật — xem task tiếp theo)
- [ ] Dashboard — load data thật từ DB
- [ ] Freemium gate modal khi vượt limit

---

## PHASE 4 — ZNS Notification

### 4.1 ZnsService
- [x] `ZnsService::send()` skeleton — gọi Zalo API, log vào notification_logs
- [x] `SendZnsReminderJob` — queue job, 3 retries
- [x] `ScheduleZnsReminders` command — 7:00 sáng hàng ngày
- [ ] Fix: command dùng `recipients()` cũ (hasMany), cần update sang belongsToMany
- [ ] `ResetMonthlyZnsCount` command — chạy ngày 1 hàng tháng
- [ ] Test dispatch job thủ công

### 4.2 ZNS Setup (chờ credentials)
- [ ] Đăng ký Zalo OA + verify doanh nghiệp
- [ ] Submit + duyệt ZNS template (3–7 ngày)
- [ ] Test gửi ZNS thật

---

## PHASE 5 — AI Agent

### 5.1 GeminiClient
- [x] Skeleton: `generateContent()`, `streamGenerateContent()`, `embed()`
- [ ] Test với Gemini API key thật
- [ ] Error handling: timeout, rate limit, quota

### 5.2 Agent Tools
- [ ] `CalendarQueryTool` — hỏi lịch giỗ
- [ ] `EventTool` — CRUD bằng chat (có confirm step)
- [ ] `PrayerTool` — soạn văn khấn
- [ ] `FamilyMemoryTool` — RAG từ tài liệu gia đình

### 5.3 AgentService & SSE
- [ ] `AgentService::chat()` — orchestrator, lưu history
- [ ] SSE endpoint `GET /agent/stream`
- [ ] Alpine.js streaming chat UI

### 5.4 Views Agent
- [ ] `agent/chat.blade.php` — chat interface
- [ ] `agent/documents.blade.php` — upload tài liệu

### 5.5 RAG Pipeline
- [ ] Upload handler (txt, pdf → extract text)
- [ ] Chunk + embed → `document_chunks`
- [ ] Cosine similarity search (MySQL fulltext hoặc PHP thuần)

---

## PHASE 6 — Share Chatbot
- [ ] `ShareController` — create, show (public, no auth), destroy
- [ ] Rate limit 20 req/phút/IP
- [ ] Views: quản lý links + public chat page

---

## PHASE 7 — Export
- [ ] `ExportService` — PDF văn khấn, TXT + Excel lịch giỗ
- [ ] Views: nút tải về

---

## PHASE 8 — Subscription & Freemium Gate
- [ ] `SubscriptionController` — trang nâng cấp gói
- [ ] Manual payment flow + artisan `subscription:activate {user} {plan}`
- [ ] Freemium gate modal component (hiện khi vượt limit)
- [ ] Pricing table view

---

## PHASE 9 — Polish & Launch
- [ ] Landing page `/` — giới thiệu + CTA
- [ ] Onboarding flow sau đăng ký lần đầu
- [ ] Error pages 404 / 500
- [ ] Responsive mobile test
- [ ] Deploy VPS (Nginx + PHP 8.3 + MySQL + supervisor + cron + SSL + Let's Encrypt)

---

## BACKLOG
- [ ] Payment gateway tự động (VNPay / MoMo)
- [ ] Nhắc Rằm, Mùng 1, sinh nhật âm lịch
- [ ] Gia phả, native app, admin dashboard

---

## Ghi chú Dev

- **Lunar**: `vantran/lunar-calendar` → namespace `LucNham\LunarCalendar`. `format('j')===30` để detect tháng thiếu.
- **Recipient architecture**: global contact (user_id), gắn với event qua pivot `event_recipient(memorial_event_id, recipient_id, notify_days_before)`.
- **Auth**: Google OAuth active. OTP phone built, bật khi có SpeedSMS credit.
- **ZNS**: `ScheduleZnsReminders` cần fix sau khi đổi sang BelongsToMany.
- **AI**: Gemini HTTP client thuần, không cần SDK.
