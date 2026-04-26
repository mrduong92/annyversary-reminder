<?php

return [

    /*
     | auth_driver: 'google' | 'otp'
     |
     | 'google' — đăng nhập bằng Google OAuth (phase 1, mặc định)
     | 'otp'    — đăng nhập bằng số điện thoại + OTP SpeedSMS (phase 2)
     */
    'auth_driver' => env('AUTH_DRIVER', 'google'),

];
