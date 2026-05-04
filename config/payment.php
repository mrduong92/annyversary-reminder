<?php

return [
    'bank_name'    => env('SEPAY_BANK_CODE', 'MB'),
    'bank_account' => env('SEPAY_BANK_ACCOUNT', ''),
    'bank_owner'   => env('SEPAY_BANK_ACCOUNT_NAME', ''),

    'download_unlock_price' => 49000,
];
