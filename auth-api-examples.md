# Authentication API Examples

Đây là các ví dụ để test bộ API authentication đã tạo.

## 🚀 Khởi động server

```bash
php artisan serve
# Server sẽ chạy tại: http://localhost:8000
```

## 📝 API Endpoints

### 1. **Đăng ký tài khoản**

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "0123456789",
    "level": "beginner"
  }'
```

**Response Success:**

```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "0123456789",
            "level": "beginner",
            "preferred_sports": null,
            "preferred_position": null,
            "avatar": null,
            "roles": ["user"]
        },
        "token": "1|abc123def456...",
        "token_type": "Bearer"
    }
}
```

### 2. **Đăng nhập**

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123",
    "device_name": "iPhone 13 Pro"
  }'
```

**Response Success:**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "roles": ["user"],
            "permissions": []
        },
        "token": "2|xyz789abc123...",
        "token_type": "Bearer"
    }
}
```

### 3. **Lấy thông tin user hiện tại**

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Accept: application/json"
```

### 4. **Lấy profile chi tiết**

```bash
curl -X GET http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Accept: application/json"
```

### 5. **Cập nhật profile**

```bash
curl -X PUT http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Smith",
    "phone": "0987654321",
    "level": "intermediate"
  }'
```

### 6. **Upload avatar**

```bash
curl -X POST http://localhost:8000/api/v1/profile/avatar \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Accept: application/json" \
  -F "avatar=@/path/to/your/image.jpg"
```

### 7. **Đăng xuất**

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Accept: application/json"
```

### 8. **Đăng xuất tất cả devices**

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout-all \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Accept: application/json"
```

### 9. **Thay đổi mật khẩu**

```bash
curl -X POST http://localhost:8000/api/v1/auth/change-password \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "current_password": "password123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
  }'
```

### 10. **Lấy danh sách devices**

```bash
curl -X GET http://localhost:8000/api/v1/profile/devices \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Accept: application/json"
```

## 🔔 Push Notification APIs (Cần Bearer Token)

### 11. **Đăng ký FCM Device Token**

```bash
curl -X POST http://localhost:8000/api/v1/notifications/register-token \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "token": "fcm-device-token-here",
    "device_type": "android",
    "device_name": "Samsung Galaxy S21"
  }'
```

### 12. **Gửi Test Notification**

```bash
curl -X POST http://localhost:8000/api/v1/notifications/test \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Test Notification",
    "body": "Hello from Laravel FCM!",
    "data": {
      "custom_field": "custom_value"
    }
  }'
```

## 🛠️ Test với Postman

### Collection Import

Tạo collection trong Postman với:

1. **Environment Variables:**

    - `base_url`: `http://localhost:8000/api/v1`
    - `auth_token`: (sẽ được set sau khi login)

2. **Headers Template:**

    ```
    Content-Type: application/json
    Accept: application/json
    Authorization: Bearer {{auth_token}}
    ```

3. **Scripts để auto-set token:**

    **Post-response Script cho Login/Register:**

    ```javascript
    if (pm.response.json().success && pm.response.json().data.token) {
        pm.environment.set("auth_token", pm.response.json().data.token);
    }
    ```

## 🔒 Admin APIs (Cần role admin)

Để test admin APIs, cần:

1. **Tạo admin user:**

```bash
php artisan tinker

# Trong tinker:
$user = \App\Models\User::find(1);
$user->assignRole('admin');
```

2. **Test admin endpoints:**

### Gửi notification đến users cụ thể:

```bash
curl -X POST http://localhost:8000/api/v1/notifications/send-to-users \
  -H "Authorization: Bearer {admin-token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "user_ids": [1, 2, 3],
    "title": "System Notification",
    "body": "This is a system notification",
    "type": "general"
  }'
```

### Gửi đến tất cả users:

```bash
curl -X POST http://localhost:8000/api/v1/notifications/send-to-all \
  -H "Authorization: Bearer {admin-token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Maintenance Notice",
    "body": "System will be under maintenance tonight",
    "type": "general"
  }'
```

### Lấy thống kê:

```bash
curl -X GET http://localhost:8000/api/v1/notifications/stats \
  -H "Authorization: Bearer {admin-token}" \
  -H "Accept: application/json"
```

## 🚨 Error Responses

**401 Unauthorized:**

```json
{
    "message": "Unauthenticated."
}
```

**422 Validation Error:**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

**403 Forbidden (Role/Permission):**

```json
{
    "success": false,
    "message": "Access denied. Insufficient permissions."
}
```

## 📱 Frontend Integration Example

**JavaScript fetch example:**

```javascript
// Login
const loginResponse = await fetch("http://localhost:8000/api/v1/auth/login", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    body: JSON.stringify({
        email: "john@example.com",
        password: "password123",
        device_name: "Web Browser",
    }),
});

const loginData = await loginResponse.json();
const token = loginData.data.token;

// Use token for authenticated requests
const profileResponse = await fetch("http://localhost:8000/api/v1/profile", {
    headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
    },
});
```

---

**🎉 Bây giờ bạn có đầy đủ bộ API authentication để lấy Bearer token và sử dụng cho Push Notifications!**
