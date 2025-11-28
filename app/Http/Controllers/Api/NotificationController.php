<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Services\FCMService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Đăng ký hoặc cập nhật device token
     */
    public function registerToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $deviceToken = $user->addDeviceToken(
                $request->token,
                $request->device_type,
                $request->device_name
            );

            return response()->json([
                'success' => true,
                'message' => 'Device token registered successfully',
                'data' => $deviceToken,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register device token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa device token
     */
    public function removeToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $removed = $user->removeDeviceToken($request->token);

            return response()->json([
                'success' => true,
                'message' => $removed ? 'Device token removed successfully' : 'Device token not found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove device token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gửi notification đến users cụ thể
     */
    public function sendToUsers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:general,booking,reminder,promo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $notification = $this->fcmService->sendBatchNotification(
                $request->user_ids,
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? Notification::TYPE_GENERAL
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch notification queued successfully',
                'data' => $notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gửi notification đến tất cả users
     */
    public function sendToAllUsers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:general,booking,reminder,promo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $notification = $this->fcmService->sendToAllUsers(
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? Notification::TYPE_GENERAL
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification sent to all users successfully',
                'data' => $notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gửi notification đến users theo role
     */
    public function sendToRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:general,booking,reminder,promo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $notification = $this->fcmService->sendToUsersWithRole(
                $request->role,
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? Notification::TYPE_GENERAL
            );

            return response()->json([
                'success' => true,
                'message' => "Notification sent to users with role '{$request->role}' successfully",
                'data' => $notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gửi notification test đến user hiện tại
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'use_topic' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            if ($request->boolean('use_topic', true)) { // Default to Topic
                // Sử dụng Topic approach (khuyến nghị cho SDK 6.9.6)
                $topic = "user_test_" . $user->id;

                // Subscribe user vào topic test
                $subscriptionResult = $this->fcmService->subscribeUserToTopic($user->id, $topic);

                // Gửi qua topic
                $result = $this->fcmService->sendToTopic(
                    $topic,
                    $request->title,
                    $request->body,
                    $request->data ?? []
                );

                $method = 'Topic (Recommended)';
                $result['subscription'] = $subscriptionResult;
            } else {
                // Sử dụng Direct token approach (có thể có vấn đề với sendMulticast)
                $result = $this->fcmService->sendToUser(
                    $user->id,
                    $request->title,
                    $request->body,
                    $request->data ?? []
                );

                $method = 'Direct Token (sendMulticast/Individual)';
            }

            return response()->json([
                'success' => true,
                'message' => "Test notification sent via {$method} successfully",
                'data' => $result,
                'method' => $method,
                'sdk_version' => '6.9.6',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gửi broadcast notification qua Topic (khuyến nghị)
     */
    public function broadcastViaTopic(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:' . implode(',', [
                Notification::TYPE_GENERAL,
                Notification::TYPE_PROMOTION,
                Notification::TYPE_NEWS,
                Notification::TYPE_SYSTEM,
            ]),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $notification = $this->fcmService->sendToAllUsersViaTopic(
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? Notification::TYPE_GENERAL
            );

            return response()->json([
                'success' => true,
                'message' => 'Broadcast notification sent via Topic successfully',
                'data' => $notification,
                'method' => 'Topic Broadcast',
                'benefit' => 'More reliable than sendMulticast for SDK 6.9.6',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send broadcast notification via topic',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gửi notification tới role cụ thể qua Topic
     */
    public function sendToRoleViaTopic(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:' . implode(',', [
                Notification::TYPE_GENERAL,
                Notification::TYPE_PROMOTION,
                Notification::TYPE_NEWS,
                Notification::TYPE_SYSTEM,
            ]),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $notification = $this->fcmService->sendToUsersWithRoleViaTopic(
                $request->role,
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? Notification::TYPE_GENERAL
            );

            return response()->json([
                'success' => true,
                'message' => "Notification sent to role '{$request->role}' via Topic successfully",
                'data' => $notification,
                'method' => 'Topic Role-based',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send role notification via topic',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách notifications
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $type = $request->get('type');

        $query = Notification::query()
            ->latest('created_at');

        if ($status) {
            $query->byStatus($status);
        }

        if ($type) {
            $query->byType($type);
        }

        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Lấy chi tiết một notification
     */
    public function getNotification(int $id): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }
    }

    /**
     * Lấy thống kê notifications
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->fcmService->getNotificationStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy device tokens của user hiện tại
     */
    public function getMyDeviceTokens(): JsonResponse
    {
        try {
            $user = Auth::user();
            $tokens = $user->deviceTokens()
                ->select(['id', 'token', 'device_type', 'device_name', 'is_active', 'last_used_at', 'created_at'])
                ->latest('last_used_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tokens,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get device tokens',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
