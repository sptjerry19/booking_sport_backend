<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'user_id',
        'court_id',
        'booking_date',
        'start_time',
        'end_time',
        'total_amount',
        'discount_amount',
        'final_amount',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'cancelled_at',
        'cancellation_reason',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (!$booking->booking_number) {
                $booking->booking_number = static::generateBookingNumber();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', ['confirmed', 'paid', 'completed']);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('booking_date', $date->format('Y-m-d'));
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->format('Y-m-d'))
            ->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function isCancellable(): bool
    {
        if (!in_array($this->status, ['pending', 'confirmed', 'paid'])) {
            return false;
        }

        // Can cancel up to 2 hours before booking time
        $bookingDateTime = $this->booking_date->setTimeFromTimeString($this->start_time);
        return now()->addHours(2)->lt($bookingDateTime);
    }

    public function getDurationInMinutesAttribute(): int
    {
        $start = Carbon::createFromTimeString($this->start_time);
        $end = Carbon::createFromTimeString($this->end_time);
        return $end->diffInMinutes($start);
    }

    public function getFullDetailsAttribute(): string
    {
        return sprintf(
            '%s at %s on %s from %s to %s',
            $this->court->full_name,
            $this->court->venue->name,
            $this->booking_date->format('M d, Y'),
            Carbon::createFromTimeString($this->start_time)->format('g:i A'),
            Carbon::createFromTimeString($this->end_time)->format('g:i A')
        );
    }

    public static function generateBookingNumber(): string
    {
        $prefix = 'BK-' . now()->format('Y-');
        $lastBooking = static::where('booking_number', 'like', $prefix . '%')
            ->orderBy('booking_number', 'desc')
            ->first();

        if ($lastBooking) {
            $lastNumber = (int) substr($lastBooking->booking_number, strlen($prefix));
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return $prefix . $newNumber;
    }
}
