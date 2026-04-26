<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('zns:schedule-reminders')->dailyAt('07:00')->timezone('Asia/Ho_Chi_Minh');
