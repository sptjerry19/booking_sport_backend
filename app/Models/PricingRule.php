<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'name',
        'days_of_week',
        'start_time',
        'end_time',
        'price_per_hour',
        'slot_duration_minutes',
        'valid_from',
        'valid_until',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'price_per_hour' => 'decimal:2',
        'slot_duration_minutes' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValidForDate($query, Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_until')->orWhere('valid_until', '>=', $date);
        });
    }

    public function scopeForDayOfWeek($query, int $dayOfWeek)
    {
        return $query->whereJsonContains('days_of_week', $dayOfWeek);
    }

    public function isValidForDateTime(Carbon $dateTime): bool
    {
        // Check if rule is active
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        if ($this->valid_from && $dateTime->lt($this->valid_from)) {
            return false;
        }
        if ($this->valid_until && $dateTime->gt($this->valid_until)) {
            return false;
        }

        // Check day of week (1 = Monday, 7 = Sunday)
        if (!in_array($dateTime->dayOfWeek === 0 ? 7 : $dateTime->dayOfWeek, $this->days_of_week)) {
            return false;
        }

        // Check time range
        $time = $dateTime->format('H:i:s');
        return $time >= $this->start_time && $time < $this->end_time;
    }
}
