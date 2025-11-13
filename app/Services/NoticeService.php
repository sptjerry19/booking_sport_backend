<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NoticeService
{
    // Notice service methods would go here
    public function adminGetAllNotifications()
    {
        $notifications = Notification::query()->get()->orderBy('created_at', 'desc');
        return $notifications;
    }

    public function getNotificationById($id)
    {
        return Notification::find($id);
    }

    public function createNotification(array $params)
    {
        try {
            DB::beginTransaction();
            $data = [
                'title' => $params['title'],
                'body' => $params['body'],
                'data' => $params['data'] ?? null,
                'type' => $params['type'] ?? Notification::TYPE_GENERAL,
                'target_users' => $params['target_users'] ?? null,
                'target_topic' => $params['target_topic'] ?? null,
                'status' => Notification::STATUS_PENDING,
            ];

            $notice = Notification::create($data);
            DB::commit();
            return $notice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating notification: ' . $e->getMessage());
            return false;
        }
    }

    public function updateNotification($id, array $params)
    {
        try {
            $notification = Notification::find($id);
            if (!$notification) {
                Log::warning("Notification with ID {$id} not found for update.");
                return false;
            }

            $notification->update($params);
            return $notification;
        } catch (\Exception $e) {
            Log::error("Error updating notification: " . $e->getMessage());
            return false;
        }
    }

    public function updateNotificationStatus($notificationId, $status, $additionalData = [])
    {
        try {
            $notification = Notification::find($notificationId);
            if (!$notification) {
                Log::warning("Notification with ID {$notificationId} not found for status update.");
                return false;
            }

            $notification->status = $status;

            // Update additional fields if provided
            foreach ($additionalData as $key => $value) {
                if (in_array($key, $notification->getFillable())) {
                    $notification->$key = $value;
                }
            }

            $notification->save();
            return true;
        } catch (\Exception $e) {
            Log::error("Error updating notification status: " . $e->getMessage());
            return false;
        }
    }

    public function deleteNotification($id)
    {
        try {
            $notification = Notification::find($id);
            if (!$notification) {
                Log::warning("Notification with ID {$id} not found for deletion.");
                return false;
            }

            $notification->delete();
            return true;
        } catch (\Exception $e) {
            Log::error("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
}