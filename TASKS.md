# TASKS.md — Gia Phong

> - `[ ]` = chưa làm · `[x]` = done · `[-]` = in progress · `[~]` = defer · `⚠️` = blocked

---

## PHASE 0–3 ✅ DONE

- [x] Docker Compose (Postgres + pgvector + Nginx + PHP 8.3)
- [x] Laravel 13, env, packages (Socialite, AI SDK, dompdf, Excel, pgvector, lunar)
- [x] Migrations: tất cả bảng + pivots + family_groups + pronoun field
- [x] Models, relationships, fillable, casts
- [x] `LunarCalendarService` — 11/11 test cases (âm/dương, tháng thiếu, nhuận)
- [x] `SubscriptionService` — freemium limits
- [x] Google OAuth (active) · OTP phone auth (built, bật khi cần)
- [x] EventController CRUD — âm/dương lịch, date_type, pronoun, freemium gate
- [x] RecipientController CRUD — danh bạ độc lập, M2M pivot
- [x] PrayerController CRUD — AI soạn, lưu thủ công
- [x] Multi-family groups — switch/create/edit/delete, sidebar dropdown
- [x] Dashboard — real data theo active group
- [x] Settings page — Rằm/Mùng1 toggle, nhắc ZNS

---

## PHASE 4 — ZNS Notification

- [x] `ZnsService::send()` + log vào `notification_logs`
- [x] `SendZnsReminderJob` — queue, 3 retries
- [x] `ScheduleZnsReminders` — 7:00 sáng, BelongsToMany, notify_days_before per-link
- [x] `ScheduleLunarReminders` — 6:30 sáng, detect Rằm/Mùng1 âm lịch
- [x] `ResetMonthlyZnsCount` — ngày 1/tháng 00:05
- [x] Test end-to-end: job dispatch → queue worker → Zalo API → log `failed/Access token invalid` ✓
- ⚠️ **Blocked**: chờ Zalo OA verify + ZNS template duyệt (3-7 ngày, cần làm ngay)

---

## PHASE 5 — AI Agent ✅ DONE

- [x] Laravel AI SDK + Gemini 2.5 Flash — streaming, tool calling, conversation memory
- [x] `CalendarQueryTool` — query theo group, pronoun, readOnly share mode
- [x] `EventTool` — CRUD chat, confirm step, readOnly
- [x] `PrayerTool` — soạn văn khấn, không auto-save
- [x] `FamilyMemoryTool` — pgvector cosine similarity search
- [x] `PrayerSkill` + `CalendarSkill` — rules tách riêng, dễ chỉnh
- [x] Chat UI: streaming, Lưu văn khấn button, history reload, share panel
- [x] RAG pipeline: upload PDF/DOCX/TXT → extract → chunk → embed (Gemini) → pgvector
- [x] `agent/documents.blade.php` — upload drag&drop, list, reprocess

---

## PHASE 6 — Share Chatbot ✅ DONE

- [x] 1 share link/group, auto-create, toggle bật/tắt
- [x] Public chat `/s/{token}` — readOnly, neutral relationship language, per-member history
- [x] Rate limit 20 req/phút/IP
- [x] Nút Share trong Chat AI header

---

## PHASE 7 — Export

- [ ] `ExportService::prayerToPdf(Prayer)` — dompdf, font đẹp, layout trang trọng
- [ ] `ExportService::eventsToTxt(group)` — plain text lịch giỗ năm nay
- [ ] `ExportService::eventsToExcel(group)` — Excel với cột âm/dương/thứ/ngày
- [ ] Nút "Tải PDF" trên `prayers/show`
- [ ] Nút "Xuất lịch giỗ" trên `events/index` (TXT tất cả, Excel paid)

---

## PHASE 8 — Subscription

- [~] Defer — đang xem xét model credit-based vs subscription
- [ ] `php artisan subscription:activate {user} {plan}` — kích hoạt thủ công khi có khách
- [ ] Freemium gate modal — hiện popup nâng cấp thay vì redirect cứng
- [ ] Pricing page `/upgrade`

---

## PHASE 9 — Polish & Launch

- [ ] Landing page `/` — giới thiệu, CTA đăng ký (welcome.blade.php hiện là default Laravel)
- [ ] Onboarding flow — sau đăng ký lần đầu, hướng dẫn thêm ngày giỗ đầu tiên
- [ ] Error pages 404 / 500 — branded, có link về dashboard
- [ ] Responsive mobile — test trên phone thật hoặc DevTools
- [ ] Deploy VPS — Nginx + PHP 8.3 + Postgres + supervisor + cron + SSL

---

## BACKLOG

- [~] OTP phone auth — bật khi cần (SpeedSMS), hiện Google OAuth đủ
- [~] Payment gateway tự động (VNPay/MoMo) — manual trước, tự động sau
- [ ] Admin command reset ZNS count hàng tháng đang dùng scheduler, cần test
- [ ] Sinh nhật âm lịch (ngoài scope MVP)
- [ ] Gia phả, cáo phó (backlog xa)

---

## Ghi chú kỹ thuật

- **DB**: PostgreSQL 17 + pgvector 0.8.2. Embedding: `gemini-embedding-001` outputDimensionality=768. IVFFlat index.
- **AI**: Laravel AI SDK 0.6.x + Gemini 2.5 Flash. Bug SDK message history → fix trong `FamilyAgent::messages()`.
- **Skills**: `app/Ai/Skills/PrayerSkill.php` + `CalendarSkill.php` — inject vào tool response, dễ chỉnh.
- **Multi-family**: `active_group()` helper, middleware `SetActiveFamilyGroup`.
- **Auth**: Google OAuth active. OTP built, flip `AUTH_DRIVER=otp` khi cần.
- **ZNS**: Code đủ, blocked Zalo credentials. Cần submit OA + template ngay.
- **Pronoun**: trường `pronoun` trên `memorial_events` — danh xưng chung cho share chatbot.
