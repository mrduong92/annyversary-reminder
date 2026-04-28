# Gia Phong --- Subscription & Pricing Spec (Final)

## 1. Mục tiêu

-   Free = thu hút user, chi phí thấp, "dùng thử"
-   Mini = gói kiếm tiền chính
-   Premium = upsell cho user nặng
-   Control chi phí: ZNS + AI + infra
-   Không hiển thị quota tin nhắn cho user

------------------------------------------------------------------------

## 2. Pricing Overview

  Plan      Price      Mục tiêu
  --------- ---------- -----------------
  Free      0đ         Thu hút user
  Mini      100k/năm   Doanh thu chính
  Premium   200k/năm   Upsell

------------------------------------------------------------------------

## 3. Feature Breakdown

### 🟢 FREE

#### Core

-   Lưu gia phả không giới hạn
-   Lưu ngày giỗ không giới hạn
-   AI cơ bản:
    -   Hỏi ngày giỗ
    -   Tra cứu thông tin

#### Notification (ZNS)

-   Tối đa **3 events có nhắc**
-   Tối đa **1 recipient**
-   **1 lần nhắc**:
    -   chọn:
        -   trước X ngày
        -   hoặc đúng ngày

#### Không có

-   Nhắc rằm, mùng 1
-   Multi-reminder
-   Share gia đình
-   AI nâng cao

#### Cost nội bộ

3 × 1 × 1 = 3 messages/year (\~900đ)

------------------------------------------------------------------------

### 🟡 MINI (100k/năm)

#### Bao gồm Free +

#### Notification

-   Tối đa **10 events**
-   Tối đa **3 recipients**
-   **2 lần nhắc**:
    -   trước X ngày

    -   -   đúng ngày

#### AI

-   Tạo/sửa ngày giỗ bằng chat
-   Tra cứu thông tin gia đình
-   Giới hạn token mức trung

#### Không có

-   Nhắc rằm, mùng 1
-   Multi-level (7,5,3,1)
-   Share gia đình

#### Cost worst-case

10 × 2 × 3 = 60 messages (\~18,000đ ZNS)

------------------------------------------------------------------------

### 🔵 PREMIUM (200k/năm)

#### Bao gồm Mini +

#### Notification

-   **20 events**
-   **10 recipients**
-   Nhắc linh hoạt:
    -   7, 5, 3, 1 ngày

    -   -   đúng ngày

#### Lịch truyền thống

-   Nhắc **Rằm & Mùng 1**
    -   trước 1 ngày + đúng ngày

#### Gia đình

-   Share cho nhiều người
-   Cả nhà cùng xem & tra cứu

#### AI nâng cao

-   Soạn văn khấn
-   Upload tài liệu (RAG)
-   Import từ ảnh

------------------------------------------------------------------------

## 4. Message Control (Internal Only)

messages = events × reminders × recipients

  Plan      Limit
  --------- -----------
  Free      \~50/năm
  Mini      \~100/năm
  Premium   300/năm

------------------------------------------------------------------------

## 5. Billing & Renewal

-   Chu kỳ: năm
-   Grace period: 7--30 ngày (vẫn gửi notify)
-   Sau đó: downgrade Free

------------------------------------------------------------------------

## 6. Product Positioning

Không bán: - AI - Tin nhắn

Bán: "Sự an tâm rằng gia đình không còn quên ngày giỗ"

------------------------------------------------------------------------

## 7. Summary

  Plan      Vai trò     Giá trị
  --------- ----------- ---------
  Free      Thu hút     Thử
  Mini      Doanh thu   Đủ dùng
  Premium   Upsell      An tâm

------------------------------------------------------------------------
