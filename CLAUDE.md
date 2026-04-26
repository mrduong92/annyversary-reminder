# CLAUDE.md — Gia Phong

## Mục tiêu dự án

Web app nhắc lịch giỗ tự động cho người Việt Nam. Con cái (30–45 tuổi) setup trên web, hệ thống tự động gửi ZNS Zalo đến bố mẹ, ông bà đúng ngày. Có tính năng AI soạn văn khấn. Freemium — giới hạn tin nhắn miễn phí, thu phí gói không giới hạn.

---

## Tech Stack

| Layer | Công nghệ |
|---|---|
| Backend | Laravel 12 (PHP 8.3) |
| Frontend | Laravel Blade + Alpine.js + Tailwind CSS |
| Database | MySQL 8.0 |
| Queue/Scheduler | Laravel Queue (database driver) + Laravel Scheduler |
| Notification | Zalo ZNS API |
| AI | Google Gemini 2.0 Flash-Lite API (AI Agent) |
| Auth | Laravel Breeze + OTP số điện thoại |
| SMS OTP | SpeedSMS API (trả phí ~200đ/tin, free tier test) |
| Local dev | Docker Compose |
| Deploy | VPS Ubuntu (sau này) |

---

## Cấu trúc thư mục quan trọng

```
app/
  Http/
    Controllers/
      Auth/
      EventController.php        # Quản lý ngày giỗ
      RecipientController.php    # Quản lý người nhận ZNS
      AgentController.php        # AI Agent chat interface
      PrayerController.php       # Văn khấn AI
      SubscriptionController.php # Freemium / upgrade
      ShareController.php        # Chia sẻ chatbot với thành viên gia đình
      ExportController.php       # Export văn khấn, lịch giỗ
  Models/
    User.php
    MemorialEvent.php            # Ngày giỗ
    Recipient.php                # Người nhận ZNS
    NotificationLog.php          # Lịch sử gửi ZNS
    Prayer.php                   # Văn khấn đã lưu
    FamilyDocument.php           # Tài liệu/nhật ký gia đình upload lên
    AgentMessage.php             # Lịch sử chat với AI Agent
    FamilyShare.php              # Share chatbot cho thành viên gia đình
    Subscription.php             # Gói dịch vụ
  Services/
    LunarCalendarService.php     # Thuật toán âm lịch
    ZnsService.php               # Gửi ZNS Zalo
    SubscriptionService.php      # Kiểm tra giới hạn freemium
    AI/
      AgentService.php           # Orchestrator — nhận message, routing đến tool
      GeminiClient.php           # Wrapper Gemini 2.0 Flash-Lite API
      RagService.php             # Vector search trên tài liệu user upload
      Tools/
        PrayerTool.php           # Tool: soạn văn khấn
        EventTool.php            # Tool: thêm/sửa/xóa lịch giỗ bằng chat
        CalendarQueryTool.php    # Tool: trả lời lịch giỗ (ngày nào, thứ mấy)
        FamilyMemoryTool.php     # Tool: trả lời từ tài liệu/nhật ký gia đình (RAG)
    SmsOtpService.php            # Gửi OTP qua SpeedSMS
    ExportService.php            # Export PDF văn khấn, Excel/TXT lịch giỗ
  Jobs/
    SendZnsReminderJob.php        # Job gửi ZNS (queue)
  Console/
    Commands/
      ScheduleZnsReminders.php   # Lệnh chạy scheduler hàng ngày
resources/
  views/
    layouts/
    auth/
    events/
    prayers/
    agent/                       # Chat interface AI Agent
    share/                       # Trang shared chatbot (không cần login)
    export/                      # Preview trước khi export
    subscription/
database/
  migrations/
  seeders/
routes/
  web.php
  api.php                        # ZNS webhook callback
```

---

## Database Schema — Các bảng chính

### users
```
id, name, email, password, phone,
subscription_plan (enum: free|basic|unlimited),
subscription_expires_at,
zns_count_this_month (int, reset mỗi tháng),
timestamps
```

### memorial_events
```
id, user_id (FK),
name,                          -- Tên người mất (VD: Ông nội Nguyễn Văn A)
relationship,                  -- Quan hệ (ông nội, bà ngoại, ...)
lunar_day (tinyint),
lunar_month (tinyint),
solar_date_next (date),        -- Cache ngày dương lịch năm tới, tính lại mỗi năm
notes,
is_active (bool),
timestamps
```

### recipients
```
id, user_id (FK), memorial_event_id (FK nullable),
name, phone,                   -- Số điện thoại Zalo
notify_days_before (json),     -- VD: [1, 3] — nhắc trước 1 ngày và 3 ngày
is_active (bool),
timestamps
```

### notification_logs
```
id, user_id (FK), memorial_event_id (FK), recipient_id (FK),
zns_template_id, zns_message_id,
status (enum: pending|sent|failed),
sent_at, error_message,
timestamps
```

### prayers
```
id, user_id (FK), memorial_event_id (FK nullable),
title,                         -- VD: Văn khấn giỗ ông nội
content (text),                -- Nội dung văn khấn
ai_generated (bool),
timestamps
```

---

## Freemium Rules

```
FREE tier:
- Tối đa 3 memorial_events
- Tối đa 2 recipients
- Tối đa 5 ZNS/tháng (reset ngày 1 hàng tháng)
- AI văn khấn: 3 lần/tháng

BASIC tier (49k/năm):
- Tối đa 10 memorial_events
- Tối đa 10 recipients
- Tối đa 30 ZNS/tháng
- AI văn khấn: không giới hạn

UNLIMITED tier (149k/năm):
- Không giới hạn tất cả
- Dành cho trưởng họ / dòng họ
```

Kiểm tra giới hạn qua `SubscriptionService::canSendZns(User $user): bool`

---

## Tính năng Share Chatbot

### Concept
Mỗi user (người setup) có thể tạo **Share Link** cho chatbot gia đình mình. Người được share vào link đó có thể chat với AI Agent — AI biết đầy đủ thông tin lịch giỗ, gia phả của gia đình đó — nhưng **không thể chỉnh sửa dữ liệu**, chỉ đọc và hỏi.

### Flow
```
User A tạo share link → https://giorem.vn/share/abc123xyz
  → Share lên group Zalo gia đình
  → Người thân bấm vào link
  → Không cần đăng ký / đăng nhập
  → Chat với AI biết lịch giỗ nhà mình
  → Chỉ dùng được CalendarQueryTool + PrayerTool + FamilyMemoryTool
  → KHÔNG dùng được EventTool (không thể thêm/sửa/xóa)
```

### Bảng family_shares
```sql
id, user_id (FK — owner),
share_token (unique, random 16 chars),
name,                    -- VD: "Chatbot Gia Đình Nguyễn"
is_active (bool),
expires_at (nullable),   -- Có thể set hết hạn hoặc để vĩnh viễn
access_count (int),      -- Đếm số lần truy cập
timestamps
```

### Freemium cho Share
```
FREE:     1 share link, không có RAG trong share link
BASIC:    3 share links, có RAG
UNLIMITED: không giới hạn
```

### Lưu ý bảo mật
- Share token đủ dài (16+ chars random) để không bị brute force
- Rate limit: 20 request/phút/IP trên share link
- Owner có thể revoke link bất cứ lúc nào
- Không expose user_id hay thông tin cá nhân owner trong share link

---

## OTP Xác thực Số Điện Thoại

### Tại sao dùng số điện thoại thay email
- User Việt Nam quen dùng số điện thoại hơn email
- Số điện thoại = số Zalo để nhận ZNS sau này
- Tránh fake account dễ dàng hơn

### Provider: SpeedSMS
- API Việt Nam, ổn định, có free credit test
- ~200-300đ/tin OTP
- Không cần đăng ký brandname cho OTP thông thường (dùng đầu số VTDD)
- Tích hợp đơn giản qua REST API

### Package Laravel: `spatie/laravel-one-time-passwords`
- Quản lý OTP lifecycle (tạo, validate, expire)
- Tích hợp với bất kỳ SMS provider nào
- Hỗ trợ rate limiting built-in

### Flow đăng ký
```
Nhập số điện thoại
  → Validate format (0xxxxxxxxx)
  → SpeedSMS gửi OTP 6 số, hết hạn 5 phút
  → User nhập OTP
  → Tạo tài khoản + đăng nhập
```

### Flow đăng nhập (passwordless)
```
Nhập số điện thoại
  → Gửi OTP
  → Nhập OTP → đăng nhập
```
Không cần password — đơn giản hơn cho target user 30-45 tuổi.

### Giới hạn gửi OTP
- Tối đa 3 lần/số điện thoại/10 phút
- Tối đa 5 lần/IP/giờ
- Implement bằng Laravel RateLimiter

### Env variables bổ sung
```env
SPEEDSMS_ACCESS_TOKEN=
SPEEDSMS_SENDER=VTDD    # Đầu số mặc định, không cần brandname
```

---

## Export Tính năng

### Export Văn Khấn → PDF
- Dùng package `barryvdh/laravel-dompdf`
- Template PDF: font chữ đẹp, có tiêu đề, có thông tin người mất
- User bấm "Tải về" → download PDF ngay
- Tên file: `van-khan-{ten-nguoi-mat}-{nam}.pdf`

### Export Lịch Giỗ → Excel hoặc TXT

**Excel** dùng `maatwebsite/excel`:
```
Cột: STT | Tên người mất | Quan hệ | Ngày âm | Ngày dương (năm nay) | Thứ | Ghi chú
```

**TXT** (đơn giản hơn, không cần package):
```
LỊCH GIỖ GIA ĐÌNH — NĂM 2025
================================
1. Ông nội Nguyễn Văn A
   Ngày âm: 15/2 | Ngày dương: 14/3/2025 (Thứ Sáu)
   Còn 12 ngày nữa

2. Bà nội Trần Thị B
   ...
```

### Freemium cho Export
```
FREE:     Chỉ export TXT
BASIC:    Export PDF văn khấn + Excel lịch giỗ
UNLIMITED: Tất cả + export gia phả (phase 2)
```

---

## ZNS Integration

### Setup cần thiết
- Zalo OA đã verify doanh nghiệp (AMD AI Solutions)
- ZNS template đã được Zalo duyệt
- Credentials: `ZALO_OA_ACCESS_TOKEN`, `ZALO_ZNS_TEMPLATE_ID`

### Template ZNS mẫu
```
Nhắc lịch giỗ: {ten_nguoi_mat}
Ngày: {ngay_gio} ({ngay_duong_lich})
Gia đình: {ten_nguoi_dung}
```

### Flow gửi ZNS
```
Laravel Scheduler (chạy 7:00 sáng hàng ngày)
  → ScheduleZnsReminders command
  → Query events có solar_date_next = hôm nay + N ngày
  → Dispatch SendZnsReminderJob vào queue
  → Job gọi ZnsService::send()
  → Log kết quả vào notification_logs
```

### Lưu ý quan trọng
- Số điện thoại nhận ZNS **phải có tài khoản Zalo**
- ZNS chỉ gửi được từ OA đã verify
- Rate limit: kiểm tra docs Zalo ZNS hiện hành

---

## Thuật toán Âm lịch

**Dùng thư viện có sẵn, không tự viết.**

Package PHP đề xuất: `manhdaovan/lunar-calendar` hoặc `linhntaim9/lunar-calendar`

Yêu cầu:
- Múi giờ chuẩn GMT+7 (Việt Nam)
- Convert lunar → solar chính xác cho năm hiện tại và năm tới
- Xử lý năm nhuận âm lịch (tháng nhuận)

`LunarCalendarService` wrap thư viện này, expose:
```php
lunarToSolar(int $day, int $month, int $year): Carbon
nextOccurrence(int $lunarDay, int $lunarMonth): Carbon
```

---

## AI Agent

**Model:** `gemini-2.0-flash-lite` — rẻ nhất, context 1M token, đủ mạnh cho use case này
**Pricing:** $0.07/1M input, $0.30/1M output (rẻ hơn GPT-4o mini ~2x)

### Kiến trúc Agent

Agent dùng **Function Calling** của Gemini — model tự quyết định gọi tool nào dựa trên intent của user. Không hardcode routing.

```
User nhắn: "Giỗ ông nội năm nay vào thứ mấy?"
  → AgentService nhận message
  → Gửi lên Gemini kèm danh sách tools + context user
  → Gemini trả về: gọi CalendarQueryTool(name="ông nội")
  → AgentService execute tool → trả kết quả lên Gemini
  → Gemini format câu trả lời tự nhiên
  → Stream về UI
```

### Các Tool của Agent

**1. CalendarQueryTool** — Trả lời lịch giỗ
- Input: tên người mất hoặc quan hệ
- Output: ngày âm, ngày dương, thứ, còn bao nhiêu ngày nữa
- Ví dụ: *"Giỗ bà nội còn 12 ngày nữa, vào thứ Ba ngày 15/8"*

**2. EventTool** — CRUD lịch giỗ bằng chat tự nhiên
- Thêm: *"Thêm giỗ mẹ vào ngày 10 tháng 3 âm"*
- Sửa: *"Đổi giỗ ông nội sang ngày 12 tháng 3 âm"*
- Xóa: *"Xóa giỗ cô Lan đi"*
- Confirm trước khi execute action write

**3. PrayerTool** — Soạn văn khấn
- Input: tên người mất, quan hệ, tên người khấn, địa chỉ, loại lễ
- Output: bài văn khấn hoàn chỉnh đúng nghi thức
- Lưu vào bảng `prayers` để dùng lại
- System prompt: trang trọng, đúng truyền thống, không sáng tạo tùy tiện

**4. FamilyMemoryTool** — RAG từ tài liệu gia đình
- User upload: ảnh scan nhật ký, file text kỷ niệm, tài liệu dòng họ
- Được chunk + embed + lưu vào vector store
- Agent tìm kiếm semantic khi user hỏi về ký ức, lịch sử gia đình
- Ví dụ: *"Ông nội thích ăn gì?"* → tìm trong tài liệu → trả lời

### RAG Implementation (đơn giản nhất)

Không cần vector DB phức tạp ở MVP. Dùng **MySQL full-text search** hoặc **SQLite + sqlite-vec** cho embedding. Chỉ nâng lên pgvector/Qdrant khi cần.

```
Upload tài liệu → chunk text (500 tokens/chunk)
  → embed bằng Gemini text-embedding-004 (free tier)
  → lưu vector vào bảng document_chunks
  → khi query: embed câu hỏi → cosine similarity search → lấy top 5 chunks
  → nhét vào context Gemini
```

### Bảng bổ sung cho AI Agent

```sql
-- Lịch sử chat
agent_messages: id, user_id, role (user|assistant|tool), content, tool_name, timestamps

-- Tài liệu gia đình
family_documents: id, user_id, filename, content_type, status (processing|ready), timestamps

-- Chunks đã embed
document_chunks: id, document_id, chunk_index, content, embedding (json/vector), timestamps
```

### Freemium cho AI Agent
```
FREE:  10 tin nhắn/ngày, không có RAG, không upload tài liệu
BASIC: 50 tin nhắn/ngày, upload tối đa 3 tài liệu
UNLIMITED: không giới hạn
```

### Lưu ý kỹ thuật
- **Stream response** — dùng SSE (Server-Sent Events) để chat feel tự nhiên
- **Confirm trước write** — EventTool phải hiển thị confirm UI trước khi thêm/sửa/xóa
- **Language** — Force tiếng Việt trong system prompt
- **Timeout** — Set 30s timeout cho Gemini call, hiển thị lỗi thân thiện nếu quá hạn

---

## Docker Compose — Local Dev

```yaml
# docker-compose.yml
services:
  app:
    build: .
    ports: ["8000:8000"]
    volumes: [".:/var/www/html"]
    depends_on: [mysql]
    environment:
      - APP_ENV=local
      - DB_HOST=mysql

  mysql:
    image: mysql:8.0
    ports: ["3306:3306"]
    environment:
      MYSQL_DATABASE: gia_phong
      MYSQL_ROOT_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

Khởi động local:
```bash
docker compose up -d
php artisan migrate --seed
php artisan serve
```

Chạy scheduler local (terminal riêng):
```bash
php artisan schedule:work
```

Chạy queue worker local (terminal riêng):
```bash
php artisan queue:work
```

---

## Environment Variables

```env
APP_NAME="Gia Phong"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=gia_phong
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=database

# Zalo ZNS
ZALO_OA_ACCESS_TOKEN=
ZALO_ZNS_TEMPLATE_ID=
ZALO_ZNS_ENDPOINT=https://business.openapi.zalo.me/message/template

# Google Gemini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash-lite
GEMINI_EMBEDDING_MODEL=text-embedding-004

# SpeedSMS OTP
SPEEDSMS_ACCESS_TOKEN=
SPEEDSMS_SENDER=VTDD

# Scheduler — nhắc trước bao nhiêu ngày
REMINDER_DAYS_BEFORE=1,3
```

---

## Coding Conventions

- **Ngôn ngữ code:** English (tên biến, method, class)
- **Comment & commit message:** Tiếng Việt OK
- **Service layer bắt buộc** — Controller không chứa business logic
- **Mỗi external API** (Zalo, Claude) phải có Service class riêng, dễ mock khi test
- **Không dùng facade trong Service** — inject qua constructor
- **Validation** dùng Form Request class, không validate trong Controller
- **Error từ ZNS** phải log đầy đủ vào `notification_logs`, không silent fail

---

## Non-goals (MVP)

- Không làm gia phả
- Không làm cáo phó
- Không làm affiliate / shop
- Không làm native app
- Không làm multi-language
- Không tích hợp payment gateway (thu phí manual giai đoạn đầu)
- Không làm admin panel (dùng Tinker nếu cần)
- Share link không cần tạo tài khoản riêng — anonymous access chỉ chat
- Không làm voice input cho AI Agent
- RAG MVP dùng MySQL full-text trước, không dùng vector DB phức tạp

---

## Lệnh hay dùng

```bash
# Tạo migration
php artisan make:migration create_memorial_events_table

# Tạo Model + Migration + Controller cùng lúc
php artisan make:model MemorialEvent -mc

# Tạo Service (thủ công, không có artisan command)
# Tạo file trong app/Services/

# Tạo Job
php artisan make:job SendZnsReminderJob

# Tạo Command
php artisan make:command ScheduleZnsReminders

# Tạo Form Request
php artisan make:request StoreMemorialEventRequest

# Chạy scheduler thủ công để test
php artisan schedule:run

# Xem queue jobs
php artisan queue:monitor
```

---

## Thứ tự init dự án

1. `composer create-project laravel/laravel gia-phong`
2. Copy file này vào root
3. Setup `docker-compose.yml`
4. Config `.env`
5. Install thư viện: `composer require laravel/breeze` + lunar calendar package
6. `php artisan breeze:install blade`
   `composer require spatie/laravel-one-time-passwords`
   `composer require barryvdh/laravel-dompdf`
   `composer require maatwebsite/excel`
7. Tạo migrations theo schema trên
8. Implement `LunarCalendarService` + viết test thủ công
9. Implement `ZnsService` (mock trước, test sau khi có credentials)
10. Implement `GeminiClient` + test gọi API cơ bản
11. Implement từng Agent Tool theo thứ tự: CalendarQueryTool → EventTool → PrayerTool → FamilyMemoryTool
12. Build `AgentService` orchestrator + streaming SSE
13. Build Controllers + Views theo feature list
12. Setup Scheduler + Queue
13. Test end-to-end local trước khi deploy
