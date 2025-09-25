# Hướng Dẫn Sử Dụng FCM Push Notification

Hệ thống Push Notification sử dụng Firebase Cloud Messaging (FCM) để gửi thông báo đến nhiều thiết bị cùng lúc.

## Tính Năng Chính

### 1. Quản Lý Device Tokens

-   Đăng ký/cập nhật FCM token cho thiết bị
-   Xóa token khi không cần thiết
-   Theo dõi trạng thái và lần sử dụng cuối của token

### 2. Gửi Push Notification

-   **Batch Notification**: Gửi đến nhiều users cùng lúc
-   **Broadcast**: Gửi đến tất cả users
-   **Role-based**: Gửi đến users theo role (admin, owner, user)
-   **Individual**: Gửi đến một user cụ thể

### 3. Theo Dõi và Thống Kê

-   Lưu lịch sử tất cả notifications đã gửi
-   Thống kê số lượng thành công/thất bại
-   Tỷ lệ gửi thành công

## Cài Đặt và Cấu Hình

### 1. Cài Đặt Firebase Project

1. Truy cập [Firebase Console](https://console.firebase.google.com/)
2. Tạo project mới hoặc chọn project existing
3. Vào **Project Settings** → **Service accounts**
4. Click **Generate new private key** và tải file JSON
5. Đặt file này vào `storage/app/firebase-service-account.json`

### 2. Cấu Hình Environment Variables

Thêm vào file `.env`:

```env
# Firebase Configuration
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CREDENTIALS=storage/app/firebase-service-account.json
FIREBASE_DATABASE_URL=https://your-project-id-default-rtdb.firebaseio.com/

# FCM Configuration
FCM_SERVER_KEY=your-server-key
FCM_SENDER_ID=your-sender-id
FCM_BATCH_SIZE=500
FCM_TIMEOUT=30
FCM_RETRY_ATTEMPTS=3
```

### 3. Chạy Migration

```bash
php artisan migrate
```

## API Endpoints

### User Endpoints (Yêu cầu authentication)

#### 1. Đăng Ký Device Token

```http
POST /api/v1/notifications/register-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "token": "fcm-device-token",
  "device_type": "android",
  "device_name": "Samsung Galaxy S21"
}
```

#### 2. Xóa Device Token

```http
POST /api/v1/notifications/remove-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "token": "fcm-device-token"
}
```

#### 3. Gửi Test Notification

```http
POST /api/v1/notifications/test
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Test Notification",
  "body": "This is a test notification",
  "data": {
    "custom_field": "custom_value"
  }
}
```

#### 4. Lấy Danh Sách Device Tokens

```http
GET /api/v1/notifications/my-devices
Authorization: Bearer {token}
```

### Admin Endpoints (Yêu cầu role admin)

#### 1. Gửi Đến Users Cụ Thể

```http
POST /api/v1/notifications/send-to-users
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_ids": [1, 2, 3, 4, 5],
  "title": "Booking Reminder",
  "body": "Bạn có lịch đặt sân vào 2PM hôm nay",
  "type": "booking",
  "data": {
    "booking_id": 123,
    "court_name": "Sân tennis A1"
  }
}
```

#### 2. Gửi Đến Tất Cả Users

```http
POST /api/v1/notifications/send-to-all
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "System Maintenance",
  "body": "Hệ thống sẽ bảo trì từ 2-4 AM ngày mai",
  "type": "general"
}
```

#### 3. Gửi Đến Users Theo Role

```http
POST /api/v1/notifications/send-to-role
Authorization: Bearer {token}
Content-Type: application/json

{
  "role": "owner",
  "title": "New Policy Update",
  "body": "Chính sách mới cho chủ sân đã được cập nhật",
  "type": "general"
}
```

#### 4. Lấy Danh Sách Notifications

```http
GET /api/v1/notifications/list?per_page=15&status=completed&type=booking
Authorization: Bearer {token}
```

#### 5. Lấy Chi Tiết Notification

```http
GET /api/v1/notifications/{id}
Authorization: Bearer {token}
```

#### 6. Lấy Thống Kê

```http
GET /api/v1/notifications/stats
Authorization: Bearer {token}
```

## Sử Dụng Trong Code

### 1. Inject FCMService

```php
use App\Services\FCMService;

class BookingController extends Controller
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
}
```

### 2. Gửi Notification Trong Controller

```php
// Gửi notification khi có booking mới
public function store(StoreBookingRequest $request)
{
    $booking = Booking::create($request->validated());

    // Gửi notification đến user
    $this->fcmService->sendToUser(
        $booking->user_id,
        'Booking Confirmed',
        "Đặt sân {$booking->court->name} thành công cho {$booking->date} {$booking->time}",
        [
            'booking_id' => $booking->id,
            'type' => 'booking_confirmed'
        ]
    );
}
```

### 3. Gửi Batch Notification

```php
// Gửi notification reminder đến nhiều users
public function sendReminders()
{
    $tomorrowBookings = Booking::where('date', now()->addDay()->toDateString())
        ->with('user')
        ->get();

    $userIds = $tomorrowBookings->pluck('user_id')->unique()->toArray();

    if (!empty($userIds)) {
        $this->fcmService->sendBatchNotification(
            $userIds,
            'Booking Reminder',
            'Bạn có lịch đặt sân vào ngày mai',
            ['type' => 'reminder'],
            Notification::TYPE_REMINDER
        );
    }
}
```

## Model Usage

### 1. User Model - Thêm Device Token

```php
$user = auth()->user();

// Thêm token mới
$user->addDeviceToken(
    'fcm-token-here',
    'android',
    'Samsung Galaxy S21'
);

// Lấy active tokens
$activeTokens = $user->activeDeviceTokens;

// Xóa token
$user->removeDeviceToken('fcm-token-here');
```

### 2. Notification Model - Tracking

```php
// Lấy notifications theo status
$pendingNotifications = Notification::byStatus(Notification::STATUS_PENDING)->get();

// Lấy notifications theo type
$bookingNotifications = Notification::byType(Notification::TYPE_BOOKING)->get();

// Cập nhật trạng thái
$notification->updateSendingStatus(100, 95, 5, Notification::STATUS_COMPLETED);

// Tính success rate
echo $notification->success_rate . '%'; // 95%
```

## Queue Jobs (Khuyến nghị)

Để tránh blocking request, nên sử dụng Queue cho việc gửi batch notifications:

### 1. Tạo Job

```bash
php artisan make:job SendBatchNotificationJob
```

### 2. Implement Job

```php
<?php

namespace App\Jobs;

use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $userIds,
        public string $title,
        public string $body,
        public array $data = [],
        public string $type = 'general'
    ) {}

    public function handle(FCMService $fcmService): void
    {
        $fcmService->sendBatchNotification(
            $this->userIds,
            $this->title,
            $this->body,
            $this->data,
            $this->type
        );
    }
}
```

### 3. Dispatch Job

```php
SendBatchNotificationJob::dispatch(
    [1, 2, 3, 4, 5],
    'New Feature Available',
    'Tính năng mới đã có sẵn trong ứng dụng',
    ['feature' => 'court_booking_v2'],
    'general'
);
```

## Testing

### 1. Test với Postman

1. Đăng ký token qua endpoint `/register-token`
2. Gửi test notification qua endpoint `/test`
3. Kiểm tra logs để xem kết quả

### 2. Debug Logs

Kiểm tra file `storage/logs/laravel.log` để theo dõi:

-   Kết quả gửi notification
-   Token bị invalid
-   Lỗi kết nối Firebase

### 3. Database Check

Kiểm tra các bảng:

-   `device_tokens`: Danh sách tokens
-   `notifications`: Lịch sử gửi

## Lưu Ý Quan Trọng

### 1. Security

-   Đặt file service account JSON ngoài public directory
-   Không commit file credentials vào Git
-   Sử dụng environment variables

### 2. Performance

-   FCM có limit 500 tokens per batch
-   Sử dụng Queue cho batch lớn
-   Monitor failed tokens và cleanup

### 3. Token Management

-   Token có thể expire hoặc invalid
-   Tự động deactivate invalid tokens
-   User nên refresh token định kỳ

### 4. Error Handling

-   Retry logic cho failed requests
-   Log chi tiết để debug
-   Graceful fallback khi FCM down

## Troubleshooting

### 1. Firebase Connection Issues

```bash
# Check credentials file exists
ls -la storage/app/firebase-service-account.json

# Verify JSON format
php artisan tinker
>>> config('firebase.credentials.file')
```

### 2. Invalid Tokens

-   Tokens tự động được deactivate khi invalid
-   Check logs để xem lý do fail
-   User cần register token mới

### 3. Permission Issues

```bash
# Check file permissions
chmod 644 storage/app/firebase-service-account.json

# Check Laravel permissions
php artisan storage:link
```

## Monitoring và Analytics

### 1. Dashboard Queries

```php
// Thống kê gửi notification hôm nay
$todayStats = Notification::whereDate('created_at', today())
    ->selectRaw('
        COUNT(*) as total,
        SUM(total_success) as success,
        SUM(total_failed) as failed,
        AVG(total_success / total_sent * 100) as avg_success_rate
    ')
    ->first();

// Top notification types
$topTypes = Notification::selectRaw('type, COUNT(*) as count')
    ->groupBy('type')
    ->orderByDesc('count')
    ->limit(5)
    ->get();
```

### 2. Performance Metrics

```php
// Average processing time per notification
$avgProcessingTime = Notification::whereNotNull('sent_at')
    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, sent_at)) as avg_seconds')
    ->value('avg_seconds');
```

---

**Chúc bạn implement thành công hệ thống Push Notification! 🚀**
