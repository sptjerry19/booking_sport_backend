# Cấu Hình Firebase cho Project Yumic

## Bước 1: Cập Nhật File .env

Dựa trên Firebase config bạn cung cấp, hãy cập nhật file `.env` với các giá trị sau:

```env
# Firebase Configuration
FIREBASE_PROJECT_ID=yumic-83e7d
FIREBASE_CREDENTIALS=storage/app/firebase-service-account.json
FIREBASE_DATABASE_URL=https://yumic-83e7d-default-rtdb.firebaseio.com/

# FCM Configuration
FCM_SERVER_KEY=BJnxxyu-A79LKcjnuJ9k6VWhFS_bLnTV7DtDursl0OZzs7e3dTwgyWmGjb1dPuc-AgTb3Clp8eCoVJG4UX6vLq4
FCM_SENDER_ID=939100829101
FCM_BATCH_SIZE=500
FCM_TIMEOUT=30
FCM_RETRY_ATTEMPTS=3
```

## Bước 2: Tạo Service Account Key (Quan Trọng!)

Để gửi push notifications từ server, bạn cần Service Account Key:

### 2.1. Truy cập Firebase Console

1. Vào https://console.firebase.google.com/
2. Chọn project **yumic-83e7d**

### 2.2. Tạo Service Account

1. Vào **Project Settings** (⚙️) → **Service accounts**
2. Click **Generate new private key**
3. Tải file JSON về

### 2.3. Đặt File Credentials

1. Đặt file JSON vừa tải vào: `storage/app/firebase-service-account.json`
2. Đảm bảo file có quyền đọc: `chmod 644 storage/app/firebase-service-account.json`

## Bước 3: Lấy FCM Server Key (Legacy)

### Option 1: Firebase Console (Khuyến nghị)

1. Vào **Project Settings** → **Cloud Messaging**
2. Copy **Server key** và thay thế `your-server-key-here` trong `.env`

### Option 2: Sử dụng Service Account (Modern)

Service Account JSON đã đủ, không cần Server Key legacy.

## Bước 4: Test Cấu Hình

```bash
# Test Firebase connection
php artisan tinker

# Trong tinker:
>>> $factory = (new \Kreait\Firebase\Factory)->withServiceAccount(config('firebase.credentials.file'));
>>> $messaging = $factory->createMessaging();
>>> echo "Firebase connected successfully!";
```

## Cấu Hình Đầy Đủ File .env

```env
APP_NAME=BookingSport
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_sport
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

# Firebase Configuration cho Project Yumic
FIREBASE_PROJECT_ID=yumic-83e7d
FIREBASE_CREDENTIALS=storage/app/firebase-service-account.json
FIREBASE_DATABASE_URL=https://yumic-83e7d-default-rtdb.firebaseio.com/

# FCM Configuration
FCM_SERVER_KEY=your-server-key-from-firebase-console
FCM_SENDER_ID=939100829101
FCM_BATCH_SIZE=500
FCM_TIMEOUT=30
FCM_RETRY_ATTEMPTS=3
```

## Lưu Ý Quan Trọng

### Bảo Mật

-   Không commit file `.env` và `firebase-service-account.json` vào Git
-   Đặt file credentials ngoài public directory
-   Sử dụng environment variables trong production

### Kiểm Tra Quyền

```bash
# Kiểm tra file tồn tại
ls -la storage/app/firebase-service-account.json

# Set quyền nếu cần
chmod 644 storage/app/firebase-service-account.json
```

### Testing

```bash
# Test API endpoints
POST /api/v1/notifications/register-token
Authorization: Bearer {your-token}

{
  "token": "test-fcm-token",
  "device_type": "web",
  "device_name": "Chrome Browser"
}
```

Sau khi cấu hình xong, bạn có thể test push notification ngay!
