# 🚀 Topic Messaging API Guide

> **Khuyến nghị:** Sử dụng Topic Messaging thay vì sendMulticast cho Firebase SDK 6.9.6 + PHP 8.1

## 🔧 Current Setup

-   **Firebase SDK:** `6.9.6` (tương thích PHP 8.1)
-   **PHP Version:** `8.1.10`
-   **Status:** ✅ Stable, no SensitiveParameter errors

## 📱 API Endpoints

### 1. Test Notification (với Topic support)

**Endpoint:** `POST /api/v1/notifications/test`

**Topic approach (Default - Khuyến nghị):**

```bash
curl --location 'http://localhost:8000/api/v1/notifications/test' \
--header 'Authorization: Bearer your-token' \
--form 'title="Topic Test Message"' \
--form 'body="Testing Firebase Topic messaging"' \
--form 'use_topic=true'  # Default
```

**Direct Token approach (fallback):**

```bash
curl --location 'http://localhost:8000/api/v1/notifications/test' \
--header 'Authorization: Bearer your-token' \
--form 'title="Direct Token Test"' \
--form 'body="Testing sendMulticast/individual"' \
--form 'use_topic=false'
```

### 2. Broadcast via Topic

**Endpoint:** `POST /api/v1/notifications/broadcast-topic`

```bash
curl --location 'http://localhost:8000/api/v1/notifications/broadcast-topic' \
--header 'Authorization: Bearer your-token' \
--form 'title="System Announcement"' \
--form 'body="Important message for all users"' \
--form 'type="system"'
```

### 3. Role-based messaging via Topic

**Endpoint:** `POST /api/v1/notifications/send-role-topic`

```bash
curl --location 'http://localhost:8000/api/v1/notifications/send-role-topic' \
--header 'Authorization: Bearer your-token' \
--form 'role="venue_owner"' \
--form 'title="Message for Venue Owners"' \
--form 'body="New booking management features available"'
```

## 🎯 Benefits of Topic Messaging

### ✅ **Advantages:**

1. **More Reliable:** No 404 `/batch` endpoint errors
2. **Efficient:** One message → Multiple subscribers
3. **Scalable:** Firebase handles distribution
4. **No Token Limits:** Unlimited subscribers per topic
5. **Automatic Retry:** Firebase handles failed deliveries

### ⚠️ **sendMulticast Issues (SDK 6.9.6):**

1. **404 Errors:** `/batch` endpoint deprecated
2. **Token Limits:** Max 500 tokens per request
3. **Complex Error Handling:** Need to handle each token failure
4. **Space Issues:** Token formatting problems

## 🔄 How Topic Messaging Works

```php
// 1. Subscribe users to topic
$fcmService->subscribeUserToTopic($userId, 'topic_name');

// 2. Send message to topic (reaches all subscribers)
$fcmService->sendToTopic('topic_name', 'Title', 'Body', $data);
```

## 📊 Performance Comparison

| Method        | Reliability   | Scalability  | Error Rate |
| ------------- | ------------- | ------------ | ---------- |
| **Topic**     | ✅ High       | ✅ Unlimited | ✅ Low     |
| sendMulticast | ❌ 404 Errors | ❌ 500 limit | ❌ High    |
| Individual    | ✅ Medium     | ❌ Slow      | ✅ Medium  |

## 🎪 Use Cases

### **Topic Messaging (Recommended):**

-   ✅ Broadcast announcements
-   ✅ Role-based notifications
-   ✅ News & promotions
-   ✅ System messages
-   ✅ Sports updates

### **Direct Token (When needed):**

-   📱 Personal messages
-   📱 User-specific notifications
-   📱 Real-time updates for specific user

## 📝 Example Responses

### Topic Success Response:

```json
{
    "success": true,
    "message": "Test notification sent via Topic (Recommended) successfully",
    "data": {
        "subscription": {
            "success": 2,
            "failed": 0
        }
    },
    "method": "Topic (Recommended)",
    "sdk_version": "6.9.6"
}
```

### Direct Token Response:

```json
{
    "success": true,
    "message": "Test notification sent via Direct Token successfully",
    "data": {
        "sent": 2,
        "success": 0,
        "failed": 2
    },
    "method": "Direct Token (sendMulticast/Individual)",
    "sdk_version": "6.9.6"
}
```

## ⚡ Quick Start

1. **Register device token:**

```bash
curl -X POST '/api/v1/notifications/register-token' \
  -H 'Authorization: Bearer token' \
  -F 'token=your-fcm-token' \
  -F 'device_type=android'
```

2. **Test Topic messaging:**

```bash
curl -X POST '/api/v1/notifications/test' \
  -H 'Authorization: Bearer token' \
  -F 'title="Hello Topic!"' \
  -F 'body="Your first topic message"'
```

3. **Broadcast to all users:**

```bash
curl -X POST '/api/v1/notifications/broadcast-topic' \
  -H 'Authorization: Bearer token' \
  -F 'title="Welcome!"' \
  -F 'body="Thanks for using our app"'
```

---

## 🏆 **Conclusion**

**Topic Messaging is the BEST approach for Firebase SDK 6.9.6 + PHP 8.1**

-   No SensitiveParameter errors
-   No sendMulticast 404 issues
-   Maximum reliability and scalability

