<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Schedule;

// Nhắc lịch giỗ hàng ngày lúc 7:00 sáng
Schedule::command('zns:schedule-reminders')->dailyAt('07:00')->timezone('Asia/Ho_Chi_Minh');

// Nhắc Rằm và Mùng 1 âm lịch hàng ngày lúc 6:30 sáng
// (command tự kiểm tra có phải Rằm/Mùng 1 không)
Schedule::command('zns:lunar-reminders')->dailyAt('06:30')->timezone('Asia/Ho_Chi_Minh');

// Reset ZNS count và prayer count vào ngày 1 hàng tháng lúc 00:05
Schedule::command('zns:reset-monthly-count')->monthlyOn(1, '00:05')->timezone('Asia/Ho_Chi_Minh');

// Expire payment orders hết hạn mỗi giờ
Schedule::call([PaymentController::class, 'expireOldOrders'])->hourly();
