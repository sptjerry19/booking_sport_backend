<?php

/**
 * Script test Firebase connection
 * Chạy: php test-firebase-connection.php
 */

require_once 'vendor/autoload.php';

use Kreait\Firebase\Factory;

echo "=== FIREBASE CONNECTION TEST ===\n\n";

// Test 1: Check credentials file
$credentialsPath = 'storage/app/firebase-service-account.json';
echo "1. Kiểm tra Service Account file...\n";

if (!file_exists($credentialsPath)) {
    echo "❌ THẤT BẠI: File $credentialsPath không tồn tại!\n";
    echo "   Hãy tải Service Account JSON từ Firebase Console\n";
    exit(1);
}

echo "✅ File credentials tồn tại\n\n";

// Test 2: Load credentials
echo "2. Đọc Service Account credentials...\n";
try {
    $credentials = json_decode(file_get_contents($credentialsPath), true);

    if (!$credentials || !isset($credentials['project_id'])) {
        echo "❌ THẤT BẠI: File JSON không hợp lệ\n";
        exit(1);
    }

    echo "✅ Project ID: " . $credentials['project_id'] . "\n";
    echo "✅ Client Email: " . $credentials['client_email'] . "\n\n";
} catch (Exception $e) {
    echo "❌ THẤT BẠI: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Initialize Firebase
echo "3. Khởi tạo Firebase SDK...\n";
try {
    $factory = (new Factory)->withServiceAccount($credentialsPath);
    echo "✅ Firebase Factory khởi tạo thành công\n\n";
} catch (Exception $e) {
    echo "❌ THẤT BẠI: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Initialize Messaging
echo "4. Khởi tạo Firebase Messaging...\n";
try {
    $messaging = $factory->createMessaging();
    echo "✅ Firebase Messaging khởi tạo thành công\n\n";
} catch (Exception $e) {
    echo "❌ THẤT BẠI: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Environment variables
echo "5. Kiểm tra Environment Variables...\n";

// Load .env manually for testing
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }

    $required = ['FIREBASE_PROJECT_ID', 'FCM_SENDER_ID'];

    foreach ($required as $var) {
        if (isset($env[$var]) && !empty($env[$var]) && $env[$var] !== 'your-project-id') {
            echo "✅ $var: " . $env[$var] . "\n";
        } else {
            echo "❌ $var chưa được cấu hình hoặc còn giá trị mặc định\n";
        }
    }
} else {
    echo "❌ File .env không tồn tại\n";
}

echo "\n=== KẾT QUẢ TEST ===\n";
echo "🎉 Firebase connection test hoàn thành!\n";
echo "   Nếu tất cả đều ✅, bạn có thể sử dụng FCM push notifications.\n\n";

echo "=== BƯỚC TIẾP THEO ===\n";
echo "1. Đảm bảo đã cấu hình đúng file .env\n";
echo "2. Chạy: php artisan serve\n";
echo "3. Test API: POST /api/v1/notifications/register-token\n";
echo "4. Gửi test notification: POST /api/v1/notifications/test\n\n";
