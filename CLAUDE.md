# CLAUDE.md — Gia Phong

## Mục tiêu dự án

Nền tảng số hóa di sản gia đình người Việt. Gồm 3 nhóm tính năng chính:
1. **Nhắc lịch giỗ** — Con cái (30–45 tuổi) setup trên web, ZNS tự động đến bố mẹ/ông bà đúng ngày
2. **AI Agent gia đình** — Chat tự nhiên: soạn văn khấn, hỏi lịch giỗ, quản lý sự kiện, RAG từ tài liệu dòng họ
3. **Gia phả số** — Tạo cây gia phả, export PDF vector chuẩn in, đặt in khổ lớn ship tận nhà

Revenue: Subscription (Free/Mini/Premium) + Print-on-demand (thu tiền theo lần)

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
| PDF Export | spatie/browsershot (headless Chrome → PDF từ SVG) |
| Photo Restore | Replicate API (GFPGAN/CodeFormer — phục chế ảnh cũ) |
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
      FamilyTreeController.php   # Quản lý gia phả (CRUD members, branches)
      PrintOrderController.php   # Đặt in gia phả — POD flow
      GuestController.php        # Guest flow: tạo gia phả không cần đăng ký
  Models/
    User.php
    MemorialEvent.php            # Ngày giỗ
    Recipient.php                # Người nhận ZNS
    NotificationLog.php          # Lịch sử gửi ZNS
    Prayer.php                   # Văn khấn đã lưu
    FamilyDocument.php           # Tài liệu/nhật ký gia đình upload lên
    AgentMessage.php             # Lịch sử chat với AI Agent
    FamilyShare.php              # Share chatbot cho thành viên gia đình
    FamilyClan.php               # Tộc (họ lớn)
    FamilyBranch.php             # Chi / Cành / Phái (self-referencing)
    FamilyMember.php             # Thành viên trong gia phả
    FamilyRelationship.php       # Quan hệ giữa các thành viên
    PrintOrder.php               # Đơn đặt in
    GuestSession.php             # Session tạm cho guest chưa đăng ký
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
    FamilyTreeSvgService.php     # Generate SVG gia phả từ data động
    FamilyTreePdfService.php     # Convert SVG → PDF A0 chuẩn in (Browsershot)
    PrintOrderService.php        # Xử lý đơn in: tạo, gửi xưởng, track
    PhotoRestoreService.php      # Gọi Replicate API phục chế ảnh cũ
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
    genealogy/                   # Quản lý gia phả
    print-orders/                # Đặt in và track đơn hàng
    guest/                       # Guest flow (không cần login)
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
subscription_plan (enum: free|mini|premium),
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

## Subscription Plans

Hai gói — cả 2 đều dành cho gia đình, chỉ khác quota.
DB enum: `basic` | `advanced`

### BASIC — Gia Đình (0đ, miễn phí mãi)
```
- Gia phả: không giới hạn số lượng, không giới hạn thành viên
- Ngày giỗ: không giới hạn
- Xuất PNG + SVG: miễn phí
- ZNS: 60 tin/năm (~5 tin/tháng), 2 số điện thoại nhận
- AI Agent: 10 tin/ngày, không RAG
- Share chatbot: 1 link
- In treo tường: liên hệ Zalo báo giá
```

### ADVANCED — Đại Gia Đình (149k/năm = 12.400đ/tháng)
```
- Gia phả: không giới hạn (giống Basic)
- Ngày giỗ: không giới hạn
- Xuất PNG + SVG: miễn phí
- ZNS: 360 tin/năm (~30 tin/tháng), không giới hạn số điện thoại
- AI Agent: không giới hạn, RAG 20 tài liệu
- Share chatbot: không giới hạn
- In treo tường: liên hệ Zalo báo giá
- Import ảnh gia phả (AI)
- Nhắc Rằm + Mùng Một
```

### Cost analysis
```
ZNS cost:
  basic:    60  × 300đ = 18.000đ/năm  (chi phí/user)
  advanced: 360 × 300đ = 108.000đ/năm (chi phí/user, bù vào 149k revenue)

AI cost (Gemini Flash-Lite):
  ~0.7đ/tin → 100 tin/ngày = 70đ/ngày = 2.100đ/tháng (heavy user) → negligible

Margin advanced: 149k - 108k (ZNS) - 12k (server) ≈ 29k/năm (20%)
Tại 1.000 paid users: 149M - 120M cost = 29M/năm lãi ròng
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

### Giới hạn theo plan
```
FREE:    Không có share link
MINI:    1 share link, có RAG
PREMIUM: 10 share links, có RAG
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

### Giới hạn theo plan
```
FREE:    Chỉ export TXT lịch giỗ
MINI:    Export PDF văn khấn + Excel lịch giỗ
PREMIUM: Tất cả + export gia phả PDF A4 không watermark
```

---

## Gia Phả — Family Tree

### Cấu trúc phân cấp Việt Nam
```
Tộc (họ lớn — VD: Tộc Nguyễn Hữu)
  └── Chi (nhánh lớn — Chi trưởng, Chi thứ)
        └── Cành (nhóm gia đình gần)
              └── Hộ (1 gia đình hạt nhân)
                    └── Cá nhân
```

### Database Schema

```sql
-- Tộc
family_clans:
  id, user_id, name, founding_year, homeland, notes, logo_image, timestamps

-- Chi/Cành (self-referencing tree)
family_branches:
  id, clan_id, parent_branch_id (nullable),
  name, branch_type (enum: chi|canh|phai),
  generation_from_ancestor, timestamps

-- Thành viên
family_members:
  id, clan_id, branch_id,
  name, gender (enum: male|female),
  birth_date_lunar, birth_date_solar,
  death_date_lunar, death_date_solar,
  generation_number,
  title,           -- Cụ, Ông, Bà, Liệt sĩ...
  birthplace, burial_place,
  biography (text),
  portrait_image,
  is_alive (bool), notes, timestamps

-- Quan hệ (handle đa thê, con nuôi)
family_relationships:
  id, member_id, related_member_id,
  relationship_type (enum: spouse|child|adopted_child|parent|sibling),
  marriage_date, marriage_order,  -- vợ 1, vợ 2...
  notes, timestamps
```

### SVG Template Engine

**Nguyên tắc:** Generate SVG động từ data, không dùng template tĩnh.

```php
// FamilyTreeSvgService — render SVG từ data
class FamilyTreeSvgService {
    public function render(FamilyClan $clan, string $template = 'traditional'): string
    // Templates: traditional (đỏ/vàng), modern (trắng/xám), elegant (tím/vàng)
    // Mỗi template là 1 bộ CSS constants: màu nền, màu viền, font, spacing
    // Layout algorithm: tính tọa độ x,y từ cấu trúc cây, tránh overlap
}
```

**Template constants (ví dụ template truyền thống):**
```php
'traditional' => [
    'bg'           => '#1a0a00',
    'border'       => '#c9a84c',
    'text_primary' => '#f5d376',
    'text_muted'   => '#c9a84c',
    'male_stroke'  => '#4a7fb5',
    'female_stroke'=> '#c45c8a',
    'connector'    => '#c9a84c',
]
```

### PDF Export Pipeline

```
MySQL data
    ↓
FamilyTreeSvgService::render() → SVG string
    ↓
Blade view: resources/views/exports/family-tree.blade.php
    (chỉ chứa SVG thuần, không có HTML wrapper)
    ↓
FamilyTreePdfService dùng spatie/browsershot:
    Browsershot::url(route('export.family-tree', $clanId))
        ->paperSize(1189, 841, 'mm')  // A0 landscape
        ->deviceScaleFactor(2)        // 300dpi equivalent
        ->save(storage_path('exports/gia-pha-{id}.pdf'))
    ↓
File PDF vector A0 (~2-5MB) — chuẩn gửi xưởng in
```

**Tại sao PDF từ SVG tốt hơn JPG:**
- SVG là vector — text và đường kẻ sắc nét tuyệt đối dù in A0
- JPG bị artifact nén, text bị răng cưa khi in khổ lớn
- File JPG 10MB thực tế vẫn mờ vì JPEG compression, không phải vì thiếu pixel

**Output format duy nhất: PDF A0**
- A0 (841×1189mm) là master size
- Xưởng in tự scale xuống A1/A2 nếu cần
- Không cần generate nhiều size — 1 file đủ dùng cho mọi máy in

**Spec PDF gửi xưởng:**
```
✅ Color space: CMYK
✅ Resolution: 150+ DPI ở kích thước thật
✅ Bleed: 3-5mm mỗi cạnh
✅ Font: embedded trong PDF
✅ Vector text: không rasterize
✅ Format: PDF/X-3 hoặc PDF/X-4
```

### Guest Flow — Tạo gia phả không cần đăng ký

```
Guest vào /family-tree/create (không login)
    ↓
Nhập data gia phả (lưu vào guest_sessions với session_id)
    ↓
Preview SVG realtime
    ↓
Bấm "Tải PDF A0" → Modal thanh toán (49k)
    ↓
Thanh toán MoMo/VNPay/chuyển khoản
    ↓
Download PDF ngay lập tức
    ↓
Prompt: "Tạo tài khoản để lưu và cập nhật gia phả sau"
```

Guest data lưu vào `guest_sessions` với TTL 7 ngày, tự xóa nếu không convert thành account.

### Print-on-Demand Flow

```
User (mọi plan) bấm "Đặt in"
    ↓
Chọn gói: A1+ship / A0+ship / A0+khung+ship
    ↓
Nhập địa chỉ giao hàng
    ↓
Thanh toán 100% trước (MoMo/VNPay)
    ↓
PrintOrderService tạo order, gửi ZNS confirm
    ↓
[MANUAL] Founder nhận email/ZNS → gửi PDF cho xưởng in
    ↓
[MANUAL] Track ship, cập nhật status trong app
    ↓
User nhận ZNS update: "Đơn hàng đang giao"
    ↓
Đánh giá sau khi nhận hàng
```

**Tại sao thu 100% trước:**
- Chuẩn của ngành in ấn Việt Nam — mọi xưởng đều làm vậy
- User tin tưởng qua: preview rõ ràng + chính sách hoàn tiền nếu in lỗi + ZNS tracking

**Build trust với brand mới:**
- Show preview PDF chính xác trước khi thanh toán
- Chính sách rõ: in lỗi/hỏng → in lại miễn phí hoặc hoàn 100%
- ZNS cập nhật trạng thái đơn hàng realtime
- 10 đơn đầu: chụp ảnh sản phẩm thực tế, đăng Facebook/Zalo làm social proof

### Photo Restoration (Phase 2)

```
User upload ảnh ông bà cũ (mờ, ố vàng, rách)
    ↓
PhotoRestoreService gọi Replicate API
    (model: GFPGAN hoặc CodeFormer)
    ↓
Preview before/after
    ↓
Download ảnh đã restore: 49k
Đặt in ảnh thờ + ship:   299k
    ↓
Tích hợp: gắn ảnh đã restore vào profile trong gia phả
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

### Giới hạn theo plan
```
FREE:    10 tin nhắn/ngày, không có RAG, không upload tài liệu
MINI:    50 tin nhắn/ngày, upload tối đa 3 tài liệu, RAG enabled
PREMIUM: Không giới hạn, RAG không giới hạn
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

# Replicate (phục chế ảnh — Phase 2)
REPLICATE_API_TOKEN=

# Print orders
PRINT_ORDER_EMAIL=         # Email nhận thông báo đơn in mới
PRINT_NOTIFY_PHONE=        # Số nhận ZNS khi có đơn in mới

# Guest session TTL (ngày)
GUEST_SESSION_TTL=7

# Scheduler — nhắc trước bao nhiêu ngày
REMINDER_DAYS_BEFORE=1,3
```

---

## Coding Conventions

- **Ngôn ngữ code:** English (tên biến, method, class)
- **Comment & commit message:** Tiếng Việt OK
- **Service layer bắt buộc** — Controller không chứa business logic
- **Mỗi external API** (Zalo, Gemini, Replicate) phải có Service class riêng, dễ mock khi test
- **Không dùng facade trong Service** — inject qua constructor
- **Validation** dùng Form Request class, không validate trong Controller
- **Error từ ZNS** phải log đầy đủ vào `notification_logs`, không silent fail

---

## Non-goals (MVP)

- Không làm cáo phó
- Không làm affiliate / shop
- Không làm native app
- Không làm multi-language
- Không tích hợp payment gateway tự động (manual fulfillment giai đoạn đầu — chuyển khoản/MoMo thủ công)
- Không làm admin panel (dùng Tinker nếu cần)
- Share link không cần tạo tài khoản riêng — anonymous access chỉ chat
- Không làm voice input cho AI Agent
- RAG MVP dùng MySQL full-text trước, không dùng vector DB phức tạp
- Photo Restoration là Phase 2 — chưa implement trong MVP
- POD fulfillment hoàn toàn tự động là Phase 2 (hiện tại manual)

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
   `composer require spatie/browsershot`      # SVG → PDF
   `composer require barryvdh/laravel-dompdf` # PDF văn khấn đơn giản
   `composer require maatwebsite/excel`
   `npm install puppeteer`                    # Browsershot dependency
7. Tạo migrations theo schema trên
8. Implement `LunarCalendarService` + viết test thủ công
9. Implement `ZnsService` (mock trước, test sau khi có credentials)
10. Implement `GeminiClient` + test gọi API cơ bản
11. Implement từng Agent Tool theo thứ tự: CalendarQueryTool → EventTool → PrayerTool → FamilyMemoryTool
12. Build `AgentService` orchestrator + streaming SSE
13. Build Controllers + Views theo feature list
14. Setup Scheduler + Queue
15. Test end-to-end local trước khi deploy
