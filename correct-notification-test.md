# Cách Gọi API Test Notification Đúng

## ❌ Cách BẠN đang gọi (SAI):

```bash
curl --location --request GET 'http://localhost:8000/api/v1/notifications/test' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer YOUR_TOKEN' \
--form 'title="jkxchvjkxchvkjxhcvjkhx"' \
--form 'body="uyhdsufsudjkxchzvkjhckjxhvxc"' \
--form 'data=""'
```

## ✅ Cách ĐÚNG:

```bash
curl --location --request POST 'http://localhost:8000/api/v1/notifications/test' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer YOUR_TOKEN' \
--data-raw '{
    "title": "Test Notification Title",
    "body": "This is test notification body",
    "data": {
        "custom_field": "custom_value",
        "type": "test"
    }
}'
```

## 🔑 Các thay đổi cần thiết:

### 1. **HTTP Method**: GET → POST

-   Endpoint `/notifications/test` yêu cầu **POST** request

### 2. **Data format**: --form → --data-raw JSON

-   Sử dụng JSON trong body thay vì form data
-   Thêm header `Content-Type: application/json`

### 3. **Authorization**:

-   Endpoint này chỉ cần **user token**, không cần admin role
-   Sử dụng token từ login/register response

## 📝 Response mong đợi:

```json
{
    "success": true,
    "message": "Test notification sent successfully",
    "data": {
        "sent": 1,
        "success": 0,
        "failed": 1
    }
}
```

**Lưu ý:** `success: 0` là bình thường vì FCM token test không phải từ device thật.

## 🚀 Test với Bearer Token có sẵn:

### Lấy token mới:

```bash
curl --location --request POST 'http://localhost:8000/api/v1/auth/login' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--data-raw '{
    "email": "test1758326297@example.com",
    "password": "password123"
}'
```

### Test notification:

```bash
curl --location --request POST 'http://localhost:8000/api/v1/notifications/test' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer 8|OGKCGt5FEhqJ8DKik3X6dJjtG7xniISMHC60yaRt232a3c24' \
--data-raw '{
    "title": "Hello from API",
    "body": "This notification works perfectly!",
    "data": {
        "source": "api_test",
        "timestamp": "2024-12-20"
    }
}'
```

## 🔥 Admin Endpoints (cần admin role):

### Gửi đến tất cả users:

```bash
curl --location --request POST 'http://localhost:8000/api/v1/notifications/send-to-all' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer ADMIN_TOKEN' \
--data-raw '{
    "title": "System Announcement",
    "body": "This message goes to all users",
    "type": "general"
}'
```

### Tạo admin user:

```bash
php artisan tinker
$user = User::where('email', 'test1758326297@example.com')->first();
$user->assignRole('admin');
```
