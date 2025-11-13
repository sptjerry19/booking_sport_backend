<?php

namespace App\Console\Commands;

use App\Services\FCMService;
use Illuminate\Console\Command;
use Exception;

class TestFirebaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'firebase:test';

    /**
     * The description of the console command.
     */
    protected $description = 'Test Firebase connection and FCM service';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== FIREBASE CONNECTION TEST ===');
        $this->newLine();

        // Test 1: Check credentials file
        $this->info('1. Kiểm tra Service Account file...');
        $credentialsPath = config('firebase.credentials.file');

        if (!file_exists($credentialsPath)) {
            $this->error("❌ THẤT BẠI: File $credentialsPath không tồn tại!");
            $this->warn('   Hãy tải Service Account JSON từ Firebase Console');
            return self::FAILURE;
        }

        $this->line('✅ File credentials tồn tại');
        $this->newLine();

        // Test 2: Check project config
        $this->info('2. Kiểm tra cấu hình project...');
        $projectId = config('firebase.project_id');
        $senderId = config('firebase.fcm.sender_id');

        if (empty($projectId) || $projectId === 'your-project-id') {
            $this->error('❌ FIREBASE_PROJECT_ID chưa được cấu hình');
            return self::FAILURE;
        }

        $this->line("✅ Project ID: $projectId");

        if (empty($senderId) || $senderId === 'your-sender-id') {
            $this->error('❌ FCM_SENDER_ID chưa được cấu hình');
            return self::FAILURE;
        }

        $this->line("✅ Sender ID: $senderId");
        $this->newLine();

        // Test 3: Read credentials file
        $this->info('3. Đọc Service Account credentials...');
        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);

            if (!$credentials || !isset($credentials['project_id'])) {
                $this->error('❌ THẤT BẠI: File JSON không hợp lệ');
                return self::FAILURE;
            }

            $this->line('✅ Project ID từ file: ' . $credentials['project_id']);
            $this->line('✅ Client Email: ' . $credentials['client_email']);
            $this->newLine();
        } catch (Exception $e) {
            $this->error('❌ THẤT BẠI: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Test 4: Initialize FCM Service (through Laravel container)
        $this->info('4. Khởi tạo FCM Service...');
        try {
            $fcmService = app(FCMService::class);
            $this->line('✅ FCM Service khởi tạo thành công');
            $this->newLine();
        } catch (Exception $e) {
            $this->error('❌ THẤT BẠI: ' . $e->getMessage());
            $this->warn('Lỗi có thể do PHP version compatibility');
            return self::FAILURE;
        }

        // Test 5: Test Firebase messaging initialization
        $this->info('5. Test Firebase Messaging initialization...');
        try {
            // Test thông qua reflection để không gọi database
            $reflection = new \ReflectionClass($fcmService);
            $messagingProperty = $reflection->getProperty('messaging');
            $messagingProperty->setAccessible(true);
            $messaging = $messagingProperty->getValue($fcmService);

            if ($messaging) {
                $this->line('✅ Firebase Messaging khởi tạo thành công');
                $this->line('✅ FCM Service sẵn sàng gửi push notifications');
            } else {
                $this->error('❌ Firebase Messaging chưa được khởi tạo');
                return self::FAILURE;
            }
            $this->newLine();
        } catch (Exception $e) {
            $this->error('❌ THẤT BẠI khi test Firebase Messaging: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Success message
        $this->info('=== KẾT QUẢ TEST ===');
        $this->line('🎉 Firebase connection test hoàn thành thành công!');
        $this->newLine();

        $this->info('=== BƯỚC TIẾP THEO ===');
        $this->line('1. Test API endpoints với Postman');
        $this->line('2. Đăng ký device token: POST /api/v1/notifications/register-token');
        $this->line('3. Gửi test notification: POST /api/v1/notifications/test');

        return self::SUCCESS;
    }
}
