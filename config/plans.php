<?php

return [

    'free_trial' => [
        'users' => 1,
        'stores' => 1,
        'products' => 50,

        'features' => [
            'sales_report',
        ],
    ],

    'basic' => [
        'users' => 1,
        'stores' => 1,
        'products' => 500,

        'features' => [
            'sales_report',
            'stock_adjustment',
            'expense_tracking',
            'report_download',
        ],
    ],

    'lite' => [
        'users' => 2, // unlimited
        'stores' => 2,
        'products' => null,

        'features' => [
            'sales_report',
            'stock_adjustment',
            'expense_tracking',
            'report_download',
        ],
    ],

    'business' => [
        'users' => null,
        'stores' => null,
        'products' => null,

        'features' => [
            'sales_report',
            'stock_adjustment',
            'expense_tracking',
            'report_download',
            'discount_management',
            'customer_management',
            'profit_loss',
            'stock_transfer',
            'barcode_manager',
        ],
    ],

];