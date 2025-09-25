# Hướng Dẫn Migration FCM từ Multicast sang Topic

## Tổng Quan

Dự án đã được cập nhật để hỗ trợ **Topic-based Firebase Cloud Messaging** thay vì chỉ sử dụng `sendMulticast()`. Topic-based messaging hiệu quả hơn cho việc gửi thông báo broadcast đến nhiều users.

## Các Thay Đổi Chính

### 1. FCMService Methods Mới

#### Topic Management

```php
// Subscribe tokens vào topic
$fcmService->subscribeToTopic('news', ['token1', 'token2']);

// Unsubscribe tokens khỏi topic
$fcmService->unsubscribeFromTopic('news', ['token1', 'token2']);

// Gửi notification đến topic
$fcmService->sendToTopic('news', 'Tiêu đề', 'Nội dung');
```

#### User & Role Topic Management

```php
// Subscribe user vào topic
$fcmService->subscribeUserToTopic($userId, 'news');

// Subscribe tất cả users với role vào topic
$fcmService->subscribeRoleToTopic('venue_owner', 'venue_updates');

// Gửi đến tất cả users via topic
$fcmService->sendToAllUsersViaTopic('Tiêu đề', 'Nội dung');

// Gửi đến role via topic
$fcmService->sendToUsersWithRoleViaTopic('venue_owner', 'Tiêu đề', 'Nội dung');
```

### 2. Cập Nhật Methods Hiện Có

#### sendBatchNotification

```php
// Cách cũ (multicast)
$fcmService->sendBatchNotification($userIds, $title, $body);

// Cách mới (topic) - thêm tham số useTopic và topic
$fcmService->sendBatchNotification($userIds, $title, $body, $data, $type, true, 'custom_topic');
```

#### sendToAllUsers & sendToUsersWithRole

```php
// Thêm tham số useTopic
$fcmService->sendToAllUsers($title, $body, $data, $type, true); // Sử dụng topic
$fcmService->sendToUsersWithRole($role, $title, $body, $data, $type, true); // Sử dụng topic
```

### 3. Database Schema Changes

Đã thêm các fields mới vào bảng `notifications`:

-   `target_topic` - Topic được gửi
-   `devices_sent` - Số devices đã gửi
-   `devices_success` - Số devices gửi thành công
-   `devices_failed` - Số devices gửi thất bại

```sql
-- Migration tự động tạo
ALTER TABLE notifications ADD COLUMN target_topic VARCHAR(255) NULL;
ALTER TABLE notifications ADD COLUMN devices_sent INT DEFAULT 0;
ALTER TABLE notifications ADD COLUMN devices_success INT DEFAULT 0;
ALTER TABLE notifications ADD COLUMN devices_failed INT DEFAULT 0;
```

### 4. Configuration

Đã thêm section mới trong `config/firebase.php`:

```php
'topics' => [
    'default_prefix' => env('FCM_TOPIC_PREFIX', 'app_'),
    'auto_cleanup' => env('FCM_TOPIC_AUTO_CLEANUP', true),
    'subscription_batch_size' => env('FCM_SUBSCRIPTION_BATCH_SIZE', 1000),

    'predefined' => [
        'all_users' => 'all_users',
        'venue_owners' => 'role_venue_owner',
        'players' => 'role_player',
        'news' => 'news',
        // ...
    ],
],
```

## Khi Nào Sử Dụng Topic vs Multicast

### Sử Dụng Topic Khi:

-   ✅ Gửi broadcast messages (đến nhiều users)
-   ✅ Gửi theo role/group
-   ✅ Gửi thông báo định kỳ (news, promotions)
-   ✅ Có > 100 recipients

### Sử Dụng Multicast Khi:

-   ✅ Gửi targeted messages (đến specific users)
-   ✅ Personalised notifications
-   ✅ < 50 recipients
-   ✅ Cần tracking chi tiết từng token

## Ưu Điểm của Topic

1. **Hiệu năng**: Nhanh hơn cho broadcast messages
2. **Scalability**: Không giới hạn số lượng subscribers
3. **Bandwidth**: Giảm tải cho server
4. **Management**: Tự động quản lý invalid tokens
5. **Cost**: Giảm API calls

## Cách Migration

### Bước 1: Chạy Migration

```bash
php artisan migrate
```

### Bước 2: Cập Nhật Code Hiện Có

**Trước:**

```php
$fcmService->sendToAllUsers('Thông báo', 'Nội dung');
```

**Sau:**

```php
$fcmService->sendToAllUsers('Thông báo', 'Nội dung', [], 'general', true);
// hoặc
$fcmService->sendToAllUsersViaTopic('Thông báo', 'Nội dung');
```

### Bước 3: Thiết Lập Topics

```php
// Subscribe existing users vào topics
$fcmService->subscribeRoleToTopic('venue_owner', 'venue_updates');
$fcmService->subscribeRoleToTopic('player', 'player_updates');
```

## Ví Dụ Sử Dụng

Xem file `topic-usage-examples.php` để biết chi tiết các ví dụ sử dụng.

## Backward Compatibility

Tất cả methods cũ vẫn hoạt động bình thường. Topic support được thêm vào như optional parameters.

## Testing

```php
// Test topic functionality
$result = $fcmService->sendToTopic('test_topic', 'Test', 'Testing topic');
echo "Notification sent: " . ($result->status === 'completed' ? 'Success' : 'Failed');
```

## Environment Variables

Thêm vào `.env` (optional):

```
FCM_TOPIC_PREFIX=myapp_
FCM_TOPIC_AUTO_CLEANUP=true
FCM_SUBSCRIPTION_BATCH_SIZE=1000
```

## Lưu Ý Quan Trọng

1. **Topic Names**: Phải tuân theo pattern `/^[a-zA-Z0-9-_.~%]+$/`
2. **Max Length**: Topic name tối đa 100 ký tự
3. **Subscription Limit**: Mỗi app instance có thể subscribe tối đa 2000 topics
4. **Persistence**: Topic subscriptions được lưu trữ trên device

## Troubleshooting

### Lỗi thường gặp:

1. **Invalid topic name**: Sử dụng chỉ các ký tự hợp lệ
2. **Subscription failed**: Kiểm tra token validity
3. **Topic not found**: Đảm bảo đã có ít nhất 1 subscriber

### Debug:

```php
Log::info('Topic subscription result', $subscriptionResult);
```
