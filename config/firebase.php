<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình Firebase cho ứng dụng
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID', ''),

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-service-account.json')),
    ],

    'database_url' => env('FIREBASE_DATABASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | FCM (Firebase Cloud Messaging) Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho Push Notifications
    |
    */

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY', ''),
        'sender_id' => env('FCM_SENDER_ID', ''),
        'batch_size' => env('FCM_BATCH_SIZE', 500), // Số lượng token tối đa trong mỗi batch
        'timeout' => env('FCM_TIMEOUT', 30), // Timeout cho request (giây)
        'retry_attempts' => env('FCM_RETRY_ATTEMPTS', 3), // Số lần thử lại khi thất bại
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Cài đặt cho notifications
    |
    */

    'notifications' => [
        'sound' => 'default',
        'badge' => 1,
        'priority' => 'high',
        'ttl' => 86400, // Time to live (seconds) - 24 hours
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
    ],

    /*
    |--------------------------------------------------------------------------
    | Topic Management Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho Topic-based messaging
    |
    */

    'topics' => [
        'default_prefix' => env('FCM_TOPIC_PREFIX', 'app_'), // Prefix cho topic names
        'auto_cleanup' => env('FCM_TOPIC_AUTO_CLEANUP', true), // Tự động cleanup unused topics
        'cleanup_interval' => env('FCM_TOPIC_CLEANUP_INTERVAL', 86400), // Interval cleanup (seconds) - 24 hours
        'subscription_batch_size' => env('FCM_SUBSCRIPTION_BATCH_SIZE', 1000), // Batch size cho subscription

        // Predefined topics
        'predefined' => [
            'all_users' => 'all_users',
            'venue_owners' => 'role_venue_owner',
            'players' => 'role_player',
            'admins' => 'role_admin',
            'news' => 'news',
            'promotions' => 'promotions',
            'maintenance' => 'maintenance',
        ],

        // Topic naming rules
        'naming' => [
            'max_length' => 100,
            'allowed_pattern' => '/^[a-zA-Z0-9-_.~%]+$/', // Regex pattern for valid topic names
        ],
    ],
];
