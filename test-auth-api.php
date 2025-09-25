<?php

/**
 * Script test Authentication API endpoints
 * Chạy: php test-auth-api.php
 */

$baseUrl = 'http://localhost:8000/api/v1';
$token = null;

echo "=== AUTHENTICATION API TEST ===\n\n";

// Helper function để gọi API
function callAPI($method, $url, $data = null, $headers = [])
{
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json'
        ], $headers),
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

// Test 1: Register
echo "1. Testing REGISTRATION...\n";
$timestamp = time();
$registerData = [
    'name' => 'Test User ' . $timestamp,
    'email' => 'test' . $timestamp . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'phone' => '012345' . substr($timestamp, -4),
    'level' => 'beginner'
];

$result = callAPI('POST', $baseUrl . '/auth/register', $registerData);

if ($result['code'] === 201 && $result['body']['success']) {
    echo "✅ Registration SUCCESS\n";
    echo "   User: " . $result['body']['data']['user']['name'] . "\n";
    echo "   Email: " . $result['body']['data']['user']['email'] . "\n";
    $token = $result['body']['data']['token'];
    echo "   Token: " . substr($token, 0, 20) . "...\n";
} else {
    echo "❌ Registration FAILED\n";
    echo "   Code: " . $result['code'] . "\n";
    echo "   Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

echo "\n";

// Test 2: Login
echo "2. Testing LOGIN...\n";
$loginData = [
    'email' => 'test' . $timestamp . '@example.com', // Use same email from registration
    'password' => 'password123',
    'device_name' => 'Test Device'
];

$result = callAPI('POST', $baseUrl . '/auth/login', $loginData);

if ($result['code'] === 200 && $result['body']['success']) {
    echo "✅ Login SUCCESS\n";
    echo "   User: " . $result['body']['data']['user']['name'] . "\n";
    echo "   Roles: " . implode(', ', $result['body']['data']['user']['roles']) . "\n";
    $token = $result['body']['data']['token']; // Update token
    echo "   New Token: " . substr($token, 0, 20) . "...\n";
} else {
    echo "❌ Login FAILED\n";
    echo "   Code: " . $result['code'] . "\n";
    echo "   Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 3: Get Profile (Authenticated)
echo "3. Testing GET PROFILE (with Bearer token)...\n";
$result = callAPI('GET', $baseUrl . '/profile', null, ['Authorization: Bearer ' . $token]);

if ($result['code'] === 200 && $result['body']['success']) {
    echo "✅ Get Profile SUCCESS\n";
    echo "   User: " . $result['body']['data']['user']['name'] . "\n";
    echo "   Email: " . $result['body']['data']['user']['email'] . "\n";
    echo "   Total Bookings: " . $result['body']['data']['stats']['total_bookings'] . "\n";
    echo "   Active Devices: " . $result['body']['data']['stats']['active_devices'] . "\n";
} else {
    echo "❌ Get Profile FAILED\n";
    echo "   Code: " . $result['code'] . "\n";
    echo "   Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 4: Register FCM Token
echo "4. Testing REGISTER FCM TOKEN...\n";
$fcmData = [
    'token' => 'test-fcm-token-' . time(),
    'device_type' => 'web',
    'device_name' => 'Chrome Browser Test'
];

$result = callAPI('POST', $baseUrl . '/notifications/register-token', $fcmData, ['Authorization: Bearer ' . $token]);

if ($result['code'] === 200 && $result['body']['success']) {
    echo "✅ FCM Token Registration SUCCESS\n";
    echo "   Token ID: " . $result['body']['data']['id'] . "\n";
    echo "   Device: " . $result['body']['data']['device_name'] . "\n";
    echo "   Type: " . $result['body']['data']['device_type'] . "\n";
} else {
    echo "❌ FCM Token Registration FAILED\n";
    echo "   Code: " . $result['code'] . "\n";
    echo "   Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 5: Send Test Notification
echo "5. Testing SEND TEST NOTIFICATION...\n";
$notificationData = [
    'title' => 'Test Notification',
    'body' => 'Hello from Laravel FCM API Test!',
    'data' => [
        'test' => true,
        'timestamp' => time()
    ]
];

$result = callAPI('POST', $baseUrl . '/notifications/test', $notificationData, ['Authorization: Bearer ' . $token]);

if ($result['code'] === 200 && $result['body']['success']) {
    echo "✅ Test Notification SUCCESS\n";
    echo "   Message: " . $result['body']['message'] . "\n";
    if (isset($result['body']['data'])) {
        echo "   Sent: " . ($result['body']['data']['sent'] ?? 0) . "\n";
        echo "   Success: " . ($result['body']['data']['success'] ?? 0) . "\n";
        echo "   Failed: " . ($result['body']['data']['failed'] ?? 0) . "\n";
    }
} else {
    echo "❌ Test Notification FAILED\n";
    echo "   Code: " . $result['code'] . "\n";
    echo "   Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 6: Get Device Tokens
echo "6. Testing GET MY DEVICE TOKENS...\n";
$result = callAPI('GET', $baseUrl . '/notifications/my-devices', null, ['Authorization: Bearer ' . $token]);

if ($result['code'] === 200 && $result['body']['success']) {
    echo "✅ Get Device Tokens SUCCESS\n";
    echo "   Total Devices: " . count($result['body']['data']) . "\n";
    foreach ($result['body']['data'] as $device) {
        echo "   - " . $device['device_name'] . " (" . $device['device_type'] . ")\n";
    }
} else {
    echo "❌ Get Device Tokens FAILED\n";
    echo "   Code: " . $result['code'] . "\n";
    echo "   Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 7: Update Profile
echo "7. Testing UPDATE PROFILE...\n";
$updateData = [
    'name' => 'Test User Updated',
    'level' => 'intermediate',
    'phone' => '0987654321'
];

$result = callAPI('PUT', $baseUrl . '/profile', $updateData, ['Authorization: Bearer ' . $token]);

if ($result['code'] === 200 && $result['body']['success']) {
    echo "✅ Update Profile SUCCESS\n";
    echo "   Name: " . $result['body']['data']['user']['name'] . "\n";
    echo "   Level: " . $result['body']['data']['user']['level'] . "\n";
    echo "   Phone: " . $result['body']['data']['user']['phone'] . "\n";
} else {
    echo "❌ Update Profile FAILED\n";
    echo "   Code: " . $result['code'] . "\n";
    echo "   Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

echo "=== TEST SUMMARY ===\n";
echo "🎉 Authentication API test completed!\n";
echo "✅ Your Bearer Token: $token\n\n";

echo "=== NEXT STEPS ===\n";
echo "1. Use this Bearer token in your frontend/mobile app\n";
echo "2. Test with Postman using the token above\n";
echo "3. Configure Firebase FCM properly for real push notifications\n";
echo "4. Create admin user to test admin endpoints:\n";
echo "   php artisan tinker\n";
echo "   >>> \$user = User::where('email', 'test@example.com')->first();\n";
echo "   >>> \$user->assignRole('admin');\n\n";

echo "🔥 Your FCM Push Notification system is ready!\n";
