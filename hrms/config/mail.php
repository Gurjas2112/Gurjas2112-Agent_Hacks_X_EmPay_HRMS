<?php
/**
 * SMTP Configuration for EmPay HRMS
 */

return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    
    // Role-based SMTP accounts
    'accounts' => [
        'hr' => [
            'email'    => 'gsgbmcc@gmail.com',
            'password' => 'lgcaphieajowbwad',
            'name'     => 'EmPay HR Department'
        ],
        'payroll' => [
            'email'    => 'gsgbmcc@gmail.com',
            'password' => 'lgcaphieajowbwad',
            'name'     => 'EmPay Payroll Department'
        ],
        'system' => [
            'email'    => 'gsgbmcc@gmail.com',
            'password' => 'lgcaphieajowbwad',
            'name'     => 'EmPay System'
        ]
    ]
];
