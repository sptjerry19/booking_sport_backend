<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Exception;

class FCMService
{
    protected $messaging;
    protected $batchSize;
    protected $timeout;
    protected $retryAttempts;

    public function __construct()
    {
        $this->initializeFirebase();
        $this->batchSize = config('firebase.fcm.batch_size', 500);
        $this->timeout = config('firebase.fcm.timeout', 30);
        $this->retryAttempts = config('firebase.fcm.retry_attempts', 3);
    }

    /**
     * Khởi tạo Firebase Messaging
     */
    protected function initializeFirebase()
    {
        try {
            $credentialsPath = config('firebase.credentials.file');

            // Ensure we have absolute path
            if (!str_starts_with($credentialsPath, '/') && !str_contains($credentialsPath, ':\\')) {
                $credentialsPath = storage_path('app/firebase-service-account.json');
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            Log::error('Credentials path: ' . ($credentialsPath ?? 'not set'));
            throw $e;
        }
    }

    /**
     * Gửi push notification đến nhiều device cùng lúc (batch)
     */
    public function sendBatchNotification(
        array $userIds,
        string $title,
        string $body,
        array $data = [],
        string $type = Notification::TYPE_GENERAL,
        bool $useTopic = false,
        string $topic = null
    ): Notification {
        // Tạo record notification
        $notification = Notification::create([
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'type' => $type,
            'target_users' => $userIds,
            'target_topic' => $useTopic ? ($topic ?? "batch_" . time()) : null,
            'status' => Notification::STATUS_PENDING,
        ]);

        try {
            if ($useTopic) {
                $this->processBatchNotificationViaTopic($notification, $topic);
            } else {
                $this->processBatchNotification($notification);
            }
        } catch (Exception $e) {
            $notification->markAsFailed($e->getMessage());
            Log::error('Batch notification failed: ' . $e->getMessage());
        }

        return $notification;
    }

    /**
     * Gửi notification đến tất cả users
     */
    public function sendToAllUsers(
        string $title,
        string $body,
        array $data = [],
        string $type = Notification::TYPE_GENERAL,
        bool $useTopic = false
    ): Notification {
        $userIds = User::pluck('id')->toArray();
        $topic = $useTopic ? 'all_users' : null;
        return $this->sendBatchNotification($userIds, $title, $body, $data, $type, $useTopic, $topic);
    }

    /**
     * Gửi notification đến users theo role
     */
    public function sendToUsersWithRole(
        string $role,
        string $title,
        string $body,
        array $data = [],
        string $type = Notification::TYPE_GENERAL,
        bool $useTopic = false
    ): Notification {
        $userIds = User::role($role)->pluck('id')->toArray();
        $topic = $useTopic ? "role_{$role}" : null;
        return $this->sendBatchNotification($userIds, $title, $body, $data, $type, $useTopic, $topic);
    }

    /**
     * Xử lý gửi batch notification
     */
    protected function processBatchNotification(Notification $notification)
    {
        $notification->update(['status' => Notification::STATUS_SENDING]);

        // Lấy tất cả device tokens của users
        $deviceTokens = DeviceToken::whereIn('user_id', $notification->target_users)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($deviceTokens)) {
            $notification->markAsFailed('Không có device token nào được tìm thấy');
            return;
        }

        $totalSent = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        // Chia tokens thành các batch nhỏ
        $tokenBatches = array_chunk($deviceTokens, $this->batchSize);

        foreach ($tokenBatches as $tokenBatch) {
            $result = $this->sendToTokenBatch(
                $tokenBatch,
                $notification->title,
                $notification->body,
                $notification->data ?? []
            );

            $totalSent += $result['sent'];
            $totalSuccess += $result['success'];
            $totalFailed += $result['failed'];

            // Cập nhật progress
            $notification->updateSendingStatus($totalSent, $totalSuccess, $totalFailed);
        }

        // Hoàn thành
        $notification->updateSendingStatus(
            $totalSent,
            $totalSuccess,
            $totalFailed,
            Notification::STATUS_COMPLETED
        );

        Log::info("Batch notification completed", [
            'notification_id' => $notification->id,
            'total_sent' => $totalSent,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'success_rate' => $notification->success_rate
        ]);
    }

    /**
     * Xử lý gửi batch notification via topic
     */
    protected function processBatchNotificationViaTopic(Notification $notification, string $topic = null)
    {
        $notification->update(['status' => Notification::STATUS_SENDING]);

        // Lấy tất cả device tokens của users
        $deviceTokens = DeviceToken::whereIn('user_id', $notification->target_users)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($deviceTokens)) {
            $notification->markAsFailed('Không có device token nào được tìm thấy');
            return;
        }

        // Tạo topic name nếu chưa có
        if (!$topic) {
            $topic = $notification->target_topic ?? "batch_" . $notification->id;
        }

        try {
            // Subscribe các tokens vào topic
            $subscriptionResult = $this->subscribeToTopic($topic, $deviceTokens);

            // Tạo message cho topic
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(FirebaseNotification::create($notification->title, $notification->body));

            // Thêm data nếu có
            if (!empty($notification->data)) {
                $message = $message->withData($notification->data);
            }

            // Thêm platform configs
            $message = $this->addPlatformConfigs($message, $notification->title, $notification->body);

            // Gửi message
            if ($this->messaging) {
                $this->messaging->send($message);
            } else {
                throw new Exception('Firebase messaging not initialized');
            }

            // Cập nhật thống kê
            $notification->updateSendingStatus(
                count($deviceTokens),
                $subscriptionResult['success'],
                $subscriptionResult['failed'],
                Notification::STATUS_COMPLETED
            );

            // Cập nhật devices statistics
            $notification->update([
                'devices_sent' => count($deviceTokens),
                'devices_success' => $subscriptionResult['success'],
                'devices_failed' => $subscriptionResult['failed'],
            ]);

            Log::info("Topic batch notification completed", [
                'notification_id' => $notification->id,
                'topic' => $topic,
                'total_tokens' => count($deviceTokens),
                'subscribed_success' => $subscriptionResult['success'],
                'subscribed_failed' => $subscriptionResult['failed'],
            ]);
        } catch (Exception $e) {
            $notification->markAsFailed($e->getMessage());
            Log::error('Topic batch notification failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Gửi notification đến một batch tokens
     *
     * NOTES: sendMulticast() bị lỗi 404 trong Firebase SDK 6.9.6
     * do endpoint /batch đã deprecated. Dùng individual sends thay thế.
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    protected function sendToTokenBatch(array $tokens, string $title, string $body, array $data = []): array
    {
        $sent = count($tokens);
        $success = 0;
        $failed = 0;

        try {
            // Tạo message
            $message = $this->createMessage($title, $body, $data);

            // Thử sendMulticast trước, nếu lỗi thì fallback sang individual sends
            try {
                if (!$this->messaging) {
                    throw new Exception('Firebase messaging not initialized');
                }
                $report = $this->messaging->sendMulticast($message, $tokens);

                $success = $report->successes()->count();
                $failed = $report->failures()->count();

                // Xử lý các token thất bại
                foreach ($report->failures() as $failure) {
                    $failedToken = $failure->target()->value();
                    $error = $failure->error();

                    Log::warning("FCM send failed", [
                        'token' => $failedToken,
                        'error' => $error->getMessage(),
                    ]);

                    // Vô hiệu hóa token nếu không hợp lệ
                    if ($this->shouldDeactivateToken($error)) {
                        DeviceToken::where('token', $failedToken)->update(['is_active' => false]);
                    }
                }
            } catch (Exception $multicastError) {
                Log::warning("sendMulticast failed, falling back to individual sends: " . $multicastError->getMessage());

                // FALLBACK: Gửi từng token riêng lẻ
                foreach ($tokens as $token) {
                    try {
                        if ($this->messaging) {
                            $this->messaging->send($message->withTarget('token', $token));
                        } else {
                            throw new Exception('Firebase messaging not initialized');
                        }
                        $success++;
                    } catch (Exception $tokenError) {
                        $failed++;

                        Log::warning("FCM send failed", [
                            'token' => $token,
                            'error' => $tokenError->getMessage(),
                        ]);

                        // Vô hiệu hóa token nếu không hợp lệ
                        if ($this->shouldDeactivateToken($tokenError)) {
                            DeviceToken::where('token', $token)->update(['is_active' => false]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("Batch send error: " . $e->getMessage());
            $failed = $sent;
            $success = 0;
        }

        return [
            'sent' => $sent,
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * Tạo Firebase CloudMessage
     */
    protected function createMessage(string $title, string $body, array $data = []): CloudMessage
    {
        $notification = FirebaseNotification::create($title, $body);

        $message = CloudMessage::new()->withNotification($notification);

        // Thêm data nếu có
        if (!empty($data)) {
            $message = $message->withData($data);
        }

        // Cấu hình cho Android
        $androidConfig = AndroidConfig::fromArray([
            'notification' => [
                'sound' => config('firebase.notifications.sound', 'default'),
                'priority' => config('firebase.notifications.priority', 'high'),
                'click_action' => config('firebase.notifications.click_action'),
            ],
            'ttl' => config('firebase.notifications.ttl', 86400) . 's',
        ]);

        // Cấu hình cho iOS
        $apnsConfig = ApnsConfig::fromArray([
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'badge' => config('firebase.notifications.badge', 1),
                    'sound' => config('firebase.notifications.sound', 'default'),
                ],
            ],
        ]);

        return $message
            ->withAndroidConfig($androidConfig)
            ->withApnsConfig($apnsConfig);
    }

    /**
     * Kiểm tra có nên vô hiệu hóa token không
     * Fix: Firebase SDK 6.9 Exception không có method code(), dùng getMessage()
     */
    protected function shouldDeactivateToken($error): bool
    {
        $errorMessage = $error->getMessage();

        // Các lỗi cần vô hiệu hóa token (check trong message)
        $deactivateMessages = [
            'INVALID_ARGUMENT',
            'UNREGISTERED',
            'NOT_FOUND',
            'SENDER_ID_MISMATCH',
            'REGISTRATION_TOKEN_NOT_REGISTERED',
            'registration token is not a valid FCM registration token',
            'Requested entity was not found',
        ];

        foreach ($deactivateMessages as $message) {
            if (stripos($errorMessage, $message) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gửi notification đến một device token
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): bool {
        try {
            $message = $this->createMessage($title, $body, $data);
            if ($this->messaging) {
                $this->messaging->send($message->withTarget('token', $token));
            } else {
                throw new Exception('Firebase messaging not initialized');
            }

            // Cập nhật last_used_at cho token
            DeviceToken::where('token', $token)->update(['last_used_at' => now()]);

            return true;
        } catch (Exception $e) {
            Log::error("Single FCM send failed", [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            // Vô hiệu hóa token nếu cần
            if ($this->shouldDeactivateToken($e)) {
                DeviceToken::where('token', $token)->update(['is_active' => false]);
            }

            return false;
        }
    }

    /**
     * Gửi notification đến một user (tất cả devices của user)
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = []
    ): array {
        $tokens = DeviceToken::where('user_id', $userId)
            // ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['sent' => 0, 'success' => 0, 'failed' => 0];
        }

        return $this->sendToTokenBatch($tokens, $title, $body, $data);
    }

    /**
     * Subscribe các tokens vào topic
     */
    public function subscribeToTopic(string $topic, array $tokens): array
    {
        try {
            if (!$this->messaging) {
                throw new Exception('Firebase messaging not initialized');
            }
            $report = $this->messaging->subscribeToTopic($topic, $tokens);

            // Xử lý cả trường hợp report là object hoặc array
            if (is_object($report) && method_exists($report, 'successes')) {
                $success = $report->successes()->count();
                $failed = $report->failures()->count();

                // Log failures
                foreach ($report->failures() as $failure) {
                    Log::warning("Topic subscription failed", [
                        'topic' => $topic,
                        'token' => $failure->target()->value(),
                        'error' => $failure->error()->getMessage(),
                    ]);
                }
            } else {
                // Fallback: giả sử tất cả thành công nếu không có thông tin chi tiết
                $success = count($tokens);
                $failed = 0;
                Log::info("Topic subscription completed (no detailed report)", [
                    'topic' => $topic,
                    'tokens_count' => count($tokens)
                ]);
            }

            Log::info("Topic subscription completed", [
                'topic' => $topic,
                'success' => $success,
                'failed' => $failed,
            ]);

            return [
                'success' => $success,
                'failed' => $failed,
                'total' => count($tokens),
            ];
        } catch (Exception $e) {
            Log::error("Topic subscription error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Unsubscribe các tokens khỏi topic
     */
    public function unsubscribeFromTopic(string $topic, array $tokens): array
    {
        try {
            if (!$this->messaging) {
                throw new Exception('Firebase messaging not initialized');
            }
            $report = $this->messaging->unsubscribeFromTopic($topic, $tokens);

            // Xử lý cả trường hợp report là object hoặc array
            if (is_object($report) && method_exists($report, 'successes')) {
                $success = $report->successes()->count();
                $failed = $report->failures()->count();

                // Log failures
                foreach ($report->failures() as $failure) {
                    Log::warning("Topic unsubscription failed", [
                        'topic' => $topic,
                        'token' => $failure->target()->value(),
                        'error' => $failure->error()->getMessage(),
                    ]);
                }
            } else {
                // Fallback: giả sử tất cả thành công nếu không có thông tin chi tiết
                $success = count($tokens);
                $failed = 0;
                Log::info("Topic unsubscription completed (no detailed report)", [
                    'topic' => $topic,
                    'tokens_count' => count($tokens)
                ]);
            }

            Log::info("Topic unsubscription completed", [
                'topic' => $topic,
                'success' => $success,
                'failed' => $failed,
            ]);

            return [
                'success' => $success,
                'failed' => $failed,
                'total' => count($tokens),
            ];
        } catch (Exception $e) {
            Log::error("Topic unsubscription error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Gửi notification đến topic
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data = [],
        string $type = Notification::TYPE_GENERAL
    ): Notification {
        // Tạo record notification
        $notification = Notification::create([
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'type' => $type,
            'target_topic' => $topic,
            'status' => Notification::STATUS_PENDING,
        ]);

        try {
            $notification->update(['status' => Notification::STATUS_SENDING]);

            // Kiểm tra xem có device tokens nào trong topic không
            $tokens = DeviceToken::active()->pluck('token')->toArray();

            if (empty($tokens)) {
                $notification->markAsFailed('Không có device token nào để gửi notification');
                return $notification;
            }

            // Subscribe tất cả tokens vào topic trước khi gửi
            $subscriptionResult = $this->subscribeToTopic($topic, $tokens);

            Log::info("Topic subscription before sending", [
                'topic' => $topic,
                'tokens_count' => count($tokens),
                'subscription_success' => $subscriptionResult['success'],
                'subscription_failed' => $subscriptionResult['failed']
            ]);

            // Tạo message cho topic
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(FirebaseNotification::create($title, $body));

            // Thêm data nếu có
            if (!empty($data)) {
                $message = $message->withData($data);
            }

            // Gửi message (không dùng platform configs để tránh lỗi)
            if ($this->messaging) {
                $result = $this->messaging->send($message);
            } else {
                throw new Exception('Firebase messaging not initialized');
            }

            Log::info("Firebase send result", [
                'result' => $result,
                'message_id' => is_array($result) && isset($result['name']) ? $result['name'] : 'no_id'
            ]);

            // Đánh dấu thành công
            $notification->update([
                'status' => Notification::STATUS_COMPLETED,
                'sent_at' => now(),
                'devices_sent' => count($tokens),
                'devices_success' => $subscriptionResult['success'],
                'devices_failed' => $subscriptionResult['failed'],
            ]);

            Log::info("Topic notification sent successfully", [
                'notification_id' => $notification->id,
                'topic' => $topic,
                'title' => $title,
            ]);
        } catch (Exception $e) {
            $notification->markAsFailed($e->getMessage());
            Log::error('Topic notification failed: ' . $e->getMessage());
        }

        return $notification;
    }

    /**
     * Subscribe user vào topic
     */
    public function subscribeUserToTopic(int $userId, string $topic): array
    {
        $tokens = DeviceToken::where('user_id', $userId)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0];
        }

        return $this->subscribeToTopic($topic, $tokens);
    }

    /**
     * Unsubscribe user khỏi topic
     */
    public function unsubscribeUserFromTopic(int $userId, string $topic): array
    {
        $tokens = DeviceToken::where('user_id', $userId)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0];
        }

        return $this->unsubscribeFromTopic($topic, $tokens);
    }

    /**
     * Subscribe tất cả users với role vào topic
     */
    public function subscribeRoleToTopic(string $role, string $topic): array
    {
        $userIds = User::role($role)->pluck('id')->toArray();
        $tokens = DeviceToken::whereIn('user_id', $userIds)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0];
        }

        return $this->subscribeToTopic($topic, $tokens);
    }

    /**
     * Gửi notification đến tất cả users sử dụng topic
     */
    public function sendToAllUsersViaTopic(
        string $title,
        string $body,
        array $data = [],
        string $type = Notification::TYPE_GENERAL,
        string $topic = 'all_users'
    ): Notification {
        // Đầu tiên, subscribe tất cả tokens vào topic
        $tokens = DeviceToken::active()->pluck('token')->toArray();

        if (!empty($tokens)) {
            $this->subscribeToTopic($topic, $tokens);
        }

        // Sau đó gửi đến topic
        return $this->sendToTopic($topic, $title, $body, $data, $type);
    }

    /**
     * Gửi notification đến users theo role sử dụng topic
     */
    public function sendToUsersWithRoleViaTopic(
        string $role,
        string $title,
        string $body,
        array $data = [],
        string $type = Notification::TYPE_GENERAL
    ): Notification {
        $topic = "role_{$role}";

        // Subscribe users với role này vào topic
        $this->subscribeRoleToTopic($role, $topic);

        // Gửi đến topic
        return $this->sendToTopic($topic, $title, $body, $data, $type);
    }

    /**
     * Thêm platform-specific configs vào message
     */
    protected function addPlatformConfigs(CloudMessage $message, string $title, string $body): CloudMessage
    {
        // Cấu hình cho Android (loại bỏ priority field không hỗ trợ)
        $androidConfig = AndroidConfig::fromArray([
            'notification' => [
                'sound' => config('firebase.notifications.sound', 'default'),
                'click_action' => config('firebase.notifications.click_action'),
            ],
            'ttl' => config('firebase.notifications.ttl', 86400) . 's',
        ]);

        // Cấu hình cho iOS
        $apnsConfig = ApnsConfig::fromArray([
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'badge' => config('firebase.notifications.badge', 1),
                    'sound' => config('firebase.notifications.sound', 'default'),
                ],
            ],
        ]);

        return $message
            ->withAndroidConfig($androidConfig)
            ->withApnsConfig($apnsConfig);
    }

    /**
     * Lấy thống kê notifications
     */
    public function getNotificationStats(): array
    {
        return [
            'total_notifications' => Notification::count(),
            'pending' => Notification::byStatus(Notification::STATUS_PENDING)->count(),
            'sending' => Notification::byStatus(Notification::STATUS_SENDING)->count(),
            'completed' => Notification::byStatus(Notification::STATUS_COMPLETED)->count(),
            'failed' => Notification::byStatus(Notification::STATUS_FAILED)->count(),
            'total_devices' => DeviceToken::active()->count(),
        ];
    }
}
