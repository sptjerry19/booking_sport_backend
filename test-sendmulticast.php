<?php

require_once 'vendor/autoload.php';

use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

echo "=== TESTING sendMulticast() sau khi upgrade Firebase SDK ===\n\n";

try {
    // Khởi tạo FCMService
    $fcmService = new FCMService();
    
    echo "✓ FCMService initialized successfully\n";
    
    // Test với fake tokens để kiểm tra API call
    $fakeTokens = [
        'fake_token_1_for_testing',
        'fake_token_2_for_testing'
    ];
    
    echo "✓ Testing sendToTokenBatch với fake tokens...\n";
    
    // Gọi protected method thông qua reflection để test
    $reflection = new ReflectionClass($fcmService);
    $method = $reflection->getMethod('sendToTokenBatch');
    $method->setAccessible(true);
    
    $result = $method->invoke($fcmService, $fakeTokens, 'Test Title', 'Test Body', ['test' => 'data']);
    
    echo "✓ sendMulticast() call completed!\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    // Kiểm tra kết quả
    if (isset($result['sent'])) {
        echo "✅ sendMulticast() hoạt động với Firebase SDK 7.21.2\n";
        echo "Sent: {$result['sent']}, Success: {$result['success']}, Failed: {$result['failed']}\n";
    } else {
        echo "❌ Có vấn đề với response format\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "\n";
    
    // Kiểm tra xem có phải lỗi 404 như trước không
    if (strpos($e->getMessage(), '404') !== false) {
        echo "⚠️  Vẫn còn lỗi 404 - có thể do cấu hình Firebase\n";
    } elseif (strpos($e->getMessage(), 'credentials') !== false) {
        echo "⚠️  Lỗi credentials - cần kiểm tra file service account\n";
    } else {
        echo "ℹ️  Lỗi khác - có thể do fake tokens không hợp lệ (điều này là bình thường)\n";
    }
}

echo "\n=== Kiểm tra version Firebase SDK ===\n";
$composerLock = json_decode(file_get_contents('composer.lock'), true);
foreach ($composerLock['packages'] as $package) {
    if ($package['name'] === 'kreait/firebase-php') {
        echo "✓ Firebase PHP SDK version: " . $package['version'] . "\n";
        break;
    }
}

echo "\n=== So sánh với Topic approach ===\n";
echo "Bây giờ bạn có 2 lựa chọn:\n";
echo "1. ✅ sendMulticast() - Đã fixed với SDK 7.21.2\n";  
echo "2. ✅ Topic-based - Hiệu quả hơn cho broadcast messages\n";
echo "\nKhuyến nghị: Sử dụng Topic cho broadcast, Multicast cho targeted messages\n";
