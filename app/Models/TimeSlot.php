<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'date',
        'start_time',
        'end_time',
        'price',
        'status',
        'pricing_rule_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'court_id', 'court_id')
            ->where('booking_date', $this->date)
            ->where('start_time', '<=', $this->start_time)
            ->where('end_time', '>', $this->start_time)
            ->whereIn('status', ['confirmed', 'paid']);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('date', $date->format('Y-m-d'));
    }

    public function scopeForCourt($query, int $courtId)
    {
        return $query->where('court_id', $courtId);
    }

    public function scopeInTimeRange($query, string $startTime, string $endTime)
    {
        return $query->where('start_time', '>=', $startTime)
            ->where('end_time', '<=', $endTime);
    }

    public function isBookable(): bool
    {
        return $this->status === 'available' &&
            $this->date->isFuture();
    }

    public function getDateTimeAttribute(): Carbon
    {
        return $this->date->setTimeFromTimeString($this->start_time);
    }
}
