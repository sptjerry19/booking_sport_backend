<?php

/**
 * Ví dụ sử dụng Topic-based FCM Messaging
 *
 * File này chứa các ví dụ về cách sử dụng topic thay vì sendMulticast()
 * trong FCMService để gửi push notifications hiệu quả hơn.
 */

use App\Services\FCMService;

// Khởi tạo service
$fcmService = new FCMService();

/*
|--------------------------------------------------------------------------
| 1. Gửi thông báo đến tất cả users sử dụng topic
|--------------------------------------------------------------------------
*/

// Cách cũ: sử dụng sendMulticast (batch processing)
$notification1 = $fcmService->sendToAllUsers(
    'Thông báo mới',
    'Hệ thống sẽ bảo trì từ 2:00 - 4:00 sáng',
    ['type' => 'maintenance'],
    'general',
    false // sử dụng multicast
);

// Cách mới: sử dụng topic
$notification2 = $fcmService->sendToAllUsers(
    'Thông báo mới',
    'Hệ thống sẽ bảo trì từ 2:00 - 4:00 sáng',
    ['type' => 'maintenance'],
    'general',
    true // sử dụng topic
);

/*
|--------------------------------------------------------------------------
| 2. Gửi thông báo theo role sử dụng topic
|--------------------------------------------------------------------------
*/

// Gửi đến tất cả venue owners
$notification3 = $fcmService->sendToUsersWithRole(
    'venue_owner',
    'Cập nhật chính sách mới',
    'Vui lòng kiểm tra chính sách mới về hoa hồng',
    ['policy_id' => 123],
    'general',
    true // sử dụng topic
);

/*
|--------------------------------------------------------------------------
| 3. Gửi trực tiếp đến topic
|--------------------------------------------------------------------------
*/

// Gửi thông báo tin tức
$newsNotification = $fcmService->sendToTopic(
    'news',
    'Tin tức mới',
    'Ứng dụng đã có tính năng đặt sân tự động!',
    ['feature' => 'auto_booking']
);

// Gửi thông báo khuyến mãi
$promoNotification = $fcmService->sendToTopic(
    'promotions',
    'Khuyến mãi đặc biệt',
    'Giảm 50% cho lần đặt sân đầu tiên!',
    ['promo_code' => 'FIRST50', 'discount' => 50]
);

/*
|--------------------------------------------------------------------------
| 4. Quản lý subscription
|--------------------------------------------------------------------------
*/

// Subscribe user vào topic
$userId = 1;
$subscriptionResult = $fcmService->subscribeUserToTopic($userId, 'news');
echo "Subscribed: {$subscriptionResult['success']}, Failed: {$subscriptionResult['failed']}\n";

// Unsubscribe user khỏi topic
$unsubscriptionResult = $fcmService->unsubscribeUserFromTopic($userId, 'news');
echo "Unsubscribed: {$unsubscriptionResult['success']}, Failed: {$unsubscriptionResult['failed']}\n";

// Subscribe tất cả venue owners vào topic
$roleSubscriptionResult = $fcmService->subscribeRoleToTopic('venue_owner', 'venue_updates');
echo "Role subscription - Success: {$roleSubscriptionResult['success']}, Failed: {$roleSubscriptionResult['failed']}\n";

/*
|--------------------------------------------------------------------------
| 5. Sử dụng các topics có sẵn (predefined trong config)
|--------------------------------------------------------------------------
*/

// Lấy các topic có sẵn từ config
$predefinedTopics = config('firebase.topics.predefined');

// Gửi đến topic all_users
$fcmService->sendToTopic(
    $predefinedTopics['all_users'],
    'Hệ thống cập nhật',
    'Phiên bản mới đã được phát hành với nhiều tính năng mới'
);

// Gửi đến topic admins
$fcmService->sendToTopic(
    $predefinedTopics['admins'],
    'Báo cáo hệ thống',
    'Hệ thống đang hoạt động ổn định. Users online: 1,250'
);

/*
|--------------------------------------------------------------------------
| 6. So sánh hiệu năng
|--------------------------------------------------------------------------
*/

echo "\n=== SO SÁNH HIỆU NĂNG ===\n";

// Test với 1000 users
$userIds = range(1, 1000);

// Đo thời gian với multicast
$start = microtime(true);
$multicastNotification = $fcmService->sendBatchNotification(
    $userIds,
    'Test Multicast',
    'Testing multicast performance',
    [],
    'general',
    false // multicast
);
$multicastTime = microtime(true) - $start;

// Đo thời gian với topic
$start = microtime(true);
$topicNotification = $fcmService->sendBatchNotification(
    $userIds,
    'Test Topic',
    'Testing topic performance',
    [],
    'general',
    true, // topic
    'performance_test'
);
$topicTime = microtime(true) - $start;

echo "Multicast time: {$multicastTime}s\n";
echo "Topic time: {$topicTime}s\n";
echo "Topic is " . ($multicastTime / $topicTime) . "x faster\n";

/*
|--------------------------------------------------------------------------
| 7. Best Practices
|--------------------------------------------------------------------------
*/

echo "\n=== BEST PRACTICES ===\n";

// 1. Sử dụng topic cho broadcast messages (gửi đến nhiều users)
echo "✓ Sử dụng topic cho broadcast messages\n";

// 2. Sử dụng multicast cho targeted messages (gửi đến specific users)
echo "✓ Sử dụng multicast cho targeted messages\n";

// 3. Tận dụng predefined topics
echo "✓ Sử dụng predefined topics để tránh hardcode\n";

// 4. Topic naming convention
$topicPrefix = config('firebase.topics.default_prefix');
$customTopic = $topicPrefix . 'event_' . date('Y_m_d');
echo "✓ Topic naming: {$customTopic}\n";

// 5. Validate topic names
$topicPattern = config('firebase.topics.naming.allowed_pattern');
$isValidTopic = preg_match($topicPattern, $customTopic);
echo "✓ Topic validation: " . ($isValidTopic ? 'Valid' : 'Invalid') . "\n";

/*
|--------------------------------------------------------------------------
| 8. Error Handling
|--------------------------------------------------------------------------
*/

try {
    // Topic với tên không hợp lệ
    $invalidTopicResult = $fcmService->sendToTopic(
        'invalid topic name!@#',
        'Test',
        'This should fail'
    );
} catch (Exception $e) {
    echo "✓ Error handling: " . $e->getMessage() . "\n";
}

echo "\n=== CẤU HÌNH TOPIC ===\n";
echo "Prefix: " . config('firebase.topics.default_prefix') . "\n";
echo "Auto cleanup: " . (config('firebase.topics.auto_cleanup') ? 'Enabled' : 'Disabled') . "\n";
echo "Subscription batch size: " . config('firebase.topics.subscription_batch_size') . "\n";
echo "Max topic name length: " . config('firebase.topics.naming.max_length') . "\n";

echo "\n=== PREDEFINED TOPICS ===\n";
foreach (config('firebase.topics.predefined') as $key => $topic) {
    echo "{$key}: {$topic}\n";
}
