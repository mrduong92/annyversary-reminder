# Gia Phong - Tree Rendering, Template & PDF Export

## Mục tiêu

- Input: dữ liệu gia phả từ DB
- Output: SVG vector → PDF chất lượng cao (in A3/A1/A0)

---

## Nguyên tắc cốt lõi

- Template là SVG vector có sẵn slot placeholder (path objects)
- Backend inject text vào đúng tọa độ center của từng slot
- Output là SVG thuần → convert PDF bằng Chromium CLI
- Không làm layout ở PHP hay JS — vị trí do designer quyết định trong SVG

> One-liner: Slot SVG + Text Injection → Chromium CLI → PDF

---

## Kiến trúc

```
DB (family_members + family_relationships)
    ↓ PHP: FamilyTreeService::buildTree()
    ↓ BFS → ordered member list theo generation
    ↓ SlotAssignmentService::assign(members, slotMap)
    ↓ FamilyTreeSvgService::inject(svgPath, assignments)
    → SVG string với <text> đã điền
    ↓ Chromium CLI --print-to-pdf
    → PDF A0 vector
```

---

## Template: Gia_Pha_3.svg

File: `vectors/Gia_Pha_3.svg` (canvas 3508 × 2480, A0 landscape)

### Slot map (Objects 2–72, sorted by y)

Mỗi slot là một `<path id="Object N">` rỗng — chỉ có khung, không có text.

| Row | Y | Objects | Số slot | Width | Height | Dùng cho |
|-----|---|---------|---------|-------|--------|----------|
| R0 | 696 | 2 | 1 | ~827 curved | 120 | Case đơn lẻ: ông tổ duy nhất |
| R1 | 907 | 71, 72 | 2 | 635 | 92 | **Case couple: Gen 0 (ông + bà)** |
| R2 | 1089 | 3 | 1 | 850 | 123 | Case đơn lẻ: bà tổ / vợ ông tổ |
| R3 | 1301 | 67–70 | 4 | 325 | 76 | Gen 1 (con, tối đa 4) |
| R4 | 1454 | 52–56 | 5 | 314 | 76 | Gen 2 (tối đa 5) |
| R5 | 1608 | 4–14 | 11 | 164 | 60 | Gen 3 (tối đa 11) |
| R6 | 1750 | 15–30, 47–51 | 21 | 121 | 70 | Gen 4 (tối đa 21) |
| R7 | 1912 | 57–66 | 10 | 164 | 70 | Gen 5 (tối đa 10) |
| R8 | 2060 | 31–46 | 16 | 159 | 70 | Gen 6 (tối đa 16) |

**Object 73** (y=88, h=2303): border decoration — bỏ qua, không phải member slot.

### Tổng capacity

- **Case couple (mặc định):** 2 + 4 + 5 + 11 + 21 + 10 + 16 = **69 người** (Gen 0→6)
- **Case đơn lẻ:** thêm Objects 2 + 3 = 71 người

---

## Hai case sử dụng

### Case A — Couple-root (mặc định, phổ biến)

Dòng họ bắt đầu từ 2 cụ ông/bà đã biết cả hai.

```
Objects 71, 72  → Gen 0: Ông (71), Bà (72)
Objects 67-70   → Gen 1: Con cái (trái → phải theo tuổi)
Objects 52-56   → Gen 2
Objects 4-14    → Gen 3
Objects 15-30, 47-51 → Gen 4
Objects 57-66   → Gen 5
Objects 31-46   → Gen 6
```

Objects 2 và 3 **bỏ trống** trong case này.

### Case B — Single-root (ít dùng)

Chỉ biết một ông tổ duy nhất (vợ không rõ hoặc không muốn hiển thị).

```
Object 2        → Gen 0: Ông tổ
Objects 71, 72  → Gen 1: Hai con/nhánh lớn
Object 3        → (bỏ hoặc dùng cho bà tổ nếu biết)
Objects 67-70   → Gen 2
...
```

---

## Slot center tính như thế nào

Mỗi slot là một path rectangle với góc cong. Center để đặt text:

```php
// Với row R3–R8 (các box hình chữ nhật đơn giản):
$centerX = $x + $width / 2;
$centerY = $y + $height / 2;

// Object 2 (hình cong): center cần tính riêng từ bounding box
// Object 2: x=1329, estimated right edge ~2157 → centerX=1743
// Object 2: y=696, h=120 → centerY=756
```

---

## Text layout trong slot

Mỗi slot có 3 dòng text (nếu đủ chiều cao), căn giữa theo chiều ngang:

```svg
<text x="{cx}" y="{cy - line_gap}" text-anchor="middle" font-size="..." fill="#2c2e35">
  {pronoun}   <!-- Ông / Bà / Cụ / ... (nhỏ, mờ) -->
</text>
<text x="{cx}" y="{cy}" text-anchor="middle" font-size="..." font-weight="bold" fill="#1a1a1a">
  {name}      <!-- Họ và tên (to, đậm) -->
</text>
<text x="{cx}" y="{cy + line_gap}" text-anchor="middle" font-size="..." fill="#593f8b">
  {lifespan}  <!-- 1920–1980 hoặc sinh 1950 (nhỏ, màu phụ) -->
</text>
```

Font size theo chiều rộng slot:
- w ≥ 600px (Objects 3, 71, 72): name 36px, pronoun/lifespan 22px
- w ≥ 300px (Objects 52–70): name 24px, pronoun/lifespan 16px
- w = 164px (Objects 4–14, 57–66): name 16px, pronoun/lifespan 12px
- w = 121–159px (Objects 15–51, 31–46): name 14px, pronoun/lifespan 11px

---

## Slot assignment algorithm

```
1. BFS cây gia phả → ordered list mỗi generation (trái → phải theo x hoặc birth_year)
2. Generation 0 → fill vào R1 slots (Objects 71, 72) trong Case A
3. Generation 1 → fill vào R3 slots (Objects 67-70), trái → phải
4. Nếu member nhiều hơn slot trong row: hiển thị tối đa, bỏ người không còn slot
5. Nếu member ít hơn slot: slot thừa giữ nguyên (rỗng, chỉ có khung)
```

---

## PDF Export

```php
$command = sprintf(
    'chromium --headless --disable-gpu --print-to-pdf=%s %s 2>&1',
    escapeshellarg($outputPath),
    escapeshellarg($svgTempPath)
);
exec($command, $output, $exitCode);
```

SVG → PDF qua Chromium giữ nguyên vector (text không bị rasterize).

---

## Không làm

- Blade render tree (layout cây trong PHP template)
- JS layout cho print
- Canvas (raster = kém chất lượng in)
- Nhúng ảnh cây vào SVG background
- Hardcode tọa độ slot (đọc từ SVG file tự động)
