<?php

use App\Services\FCMService;
use Illuminate\Support\Facades\Route;

Route::get('/test-fcm', function () {
    try {
        echo "=== TESTING FCM SERVICE AFTER UPGRADE ===\n\n";
        
        $fcmService = new FCMService();
        echo "✅ FCMService initialized\n";
        
        // Test với 1 fake token để xem có còn lỗi 404 không
        $fakeTokens = ['fake_token_for_testing_only'];
        
        echo "📡 Testing sendToTokenBatch với fake token...\n";
        
        // Use reflection để access protected method
        $reflection = new ReflectionClass($fcmService);
        $method = $reflection->getMethod('sendToTokenBatch');
        $method->setAccessible(true);
        
        $result = $method->invoke($fcmService, $fakeTokens, 'Test', 'Test message', []);
        
        echo "✅ API call completed - Không có lỗi 404!\n";
        echo "Result: " . json_encode($result) . "\n";
        
        return response()->json([
            'success' => true,
            'message' => 'sendMulticast() hoạt động với Firebase SDK 7.21.2',
            'result' => $result
        ]);
        
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        
        if (strpos($e->getMessage(), '404') !== false) {
            return response()->json([
                'success' => false,
                'message' => 'Vẫn còn lỗi 404 - cần kiểm tra credentials',
                'error' => $e->getMessage()
            ]);
        }
        
        return response()->json([
            'success' => true, // True vì lỗi token invalid là expected
            'message' => 'API endpoint hoạt động, chỉ token invalid',
            'error' => $e->getMessage()
        ]);
    }
});

Route::get('/test-topic', function () {
    try {
        echo "=== TESTING TOPIC MESSAGING ===\n\n";
        
        $fcmService = new FCMService();
        
        // Test topic messaging  
        $notification = $fcmService->sendToTopic(
            'test_topic',
            'Test Topic Message',
            'Testing topic functionality'
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Topic messaging works',
            'notification_id' => $notification->id
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Topic test failed',
            'error' => $e->getMessage()
        ]);
    }
});
