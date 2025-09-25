<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'data',
        'type',
        'target_users',
        'target_topic',
        'total_sent',
        'total_success',
        'total_failed',
        'devices_sent',
        'devices_success',
        'devices_failed',
        'status',
        'error_details',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'target_users' => 'array',
        'sent_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SENDING = 'sending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const TYPE_GENERAL = 'general';
    const TYPE_BOOKING = 'booking';
    const TYPE_REMINDER = 'reminder';
    const TYPE_PROMO = 'promo';

    /**
     * Scope để lấy notification theo trạng thái
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope để lấy notification theo loại
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Cập nhật trạng thái gửi
     */
    public function updateSendingStatus(int $sent, int $success, int $failed, string $status = null)
    {
        $updateData = [
            'total_sent' => $sent,
            'total_success' => $success,
            'total_failed' => $failed,
        ];

        if ($status) {
            $updateData['status'] = $status;
        }

        if ($status === self::STATUS_COMPLETED || $status === self::STATUS_FAILED) {
            $updateData['sent_at'] = now();
        }

        $this->update($updateData);
    }

    /**
     * Đánh dấu thất bại
     */
    public function markAsFailed(string $errorDetails = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_details' => $errorDetails,
            'sent_at' => now(),
        ]);
    }

    /**
     * Tính tỷ lệ thành công
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_sent === 0) {
            return 0;
        }

        return round(($this->total_success / $this->total_sent) * 100, 2);
    }
}
