<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\TimeSlot;
use App\Helpers\ActivityHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingService
{
    /**
     * Get bookings with filtering and pagination
     */
    public function getBookings(array $filters = []): LengthAwarePaginator
    {
        $query = Booking::with(['user:id,name,email,phone', 'court.venue:id,name,address', 'court.sport:id,name']);

        // Filter by user (if not admin/owner, only show own bookings)
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by court
        if (isset($filters['court_id'])) {
            $query->where('court_id', $filters['court_id']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment status
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by date range
        if (isset($filters['start_date'])) {
            $query->where('booking_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('booking_date', '<=', $filters['end_date']);
        }

        // Filter by booking date
        if (isset($filters['booking_date_from']) || isset($filters['booking_date_to'])) {
            $query->where('booking_date', '>=', $filters['booking_date_from'] ?? now()->format('Y-m-d'))
                ->where('booking_date', '<=', $filters['booking_date_to'] ?? now()->format('Y-m-d'));
        }

        // Search by booking number
        if (isset($filters['search'])) {
            $query->where('booking_number', 'like', "%{$filters['search']}%");
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'booking_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get booking by ID with relationships
     */
    public function getBookingById(int $bookingId, array $relations = []): ?Booking
    {
        $defaultRelations = ['user:id,name,email,phone', 'court.venue:id,name,address', 'court.sport:id,name'];
        $relations = array_merge($defaultRelations, $relations);

        return Booking::with($relations)->find($bookingId);
    }

    /**
     * Create new booking
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            // Get court
            $court = Court::findOrFail($data['court_id']);

            // Check availability
            if (!$this->isCourtAvailable($court, $data['booking_date'], $data['start_time'], $data['end_time'])) {
                throw new \Exception('Sân không còn trống trong khung giờ này.');
            }

            // Calculate price from time slots
            $price = $this->calculateBookingPrice($court, $data['booking_date'], $data['start_time'], $data['end_time']);

            // Create booking
            $booking = Booking::create([
                'user_id' => $data['user_id'] ?? auth()->id(),
                'court_id' => $data['court_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'total_amount' => $price,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'final_amount' => $price - ($data['discount_amount'] ?? 0),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'] ?? 'cash', // Default to cash if not provided
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($booking)
                ->causedBy(auth()->user())
                ->log('Booking created');

            return $booking->load(['user:id,name,email,phone', 'court.venue:id,name,address', 'court.sport:id,name']);
        });
    }

    /**
     * Update booking
     */
    public function updateBooking(Booking $booking, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $data) {
            // Check if booking can be updated
            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                throw new \Exception('Không thể cập nhật booking với trạng thái hiện tại.');
            }

            // If date/time changed, check availability
            if (isset($data['booking_date']) || isset($data['start_time']) || isset($data['end_time'])) {
                $bookingDate = $data['booking_date'] ?? $booking->booking_date->format('Y-m-d');
                $startTime = $data['start_time'] ?? $booking->start_time;
                $endTime = $data['end_time'] ?? $booking->end_time;

                // Check if new time slot is available (excluding current booking)
                $court = $booking->court;
                $isAvailable = $this->isCourtAvailableExcludingBooking(
                    $court,
                    $bookingDate,
                    $startTime,
                    $endTime,
                    $booking->id
                );

                if (!$isAvailable) {
                    throw new \Exception('Khung giờ mới không còn trống.');
                }

                // Recalculate price if time changed
                if (isset($data['start_time']) || isset($data['end_time']) || isset($data['booking_date'])) {
                    $price = $this->calculateBookingPrice($court, $bookingDate, $startTime, $endTime);
                    $data['total_amount'] = $price;
                    $data['final_amount'] = $price - ($data['discount_amount'] ?? $booking->discount_amount);
                }
            }

            $booking->update($data);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($booking)
                ->causedBy(auth()->user())
                ->log('Booking updated');

            return $booking->load(['user:id,name,email,phone', 'court.venue:id,name,address', 'court.sport:id,name']);
        });
    }

    /**
     * Delete booking
     */
    public function deleteBooking(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            // Check if booking can be deleted
            if (!in_array($booking->status, ['pending', 'cancelled'])) {
                throw new \Exception('Chỉ có thể xóa booking ở trạng thái pending hoặc cancelled.');
            }

            // Log activity before deletion
            ActivityHelper::activity()
                ->performedOn($booking)
                ->causedBy(auth()->user())
                ->log('Booking deleted');

            return $booking->delete();
        });
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason) {
            // Check if booking can be cancelled
            if (!in_array($booking->status, ['pending', 'confirmed', 'paid'])) {
                throw new \Exception('Không thể hủy booking với trạng thái hiện tại.');
            }

            if (!$booking->isCancellable()) {
                throw new \Exception('Không thể hủy booking trong vòng 2 giờ trước giờ đặt sân.');
            }

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // If paid, update payment status
            if ($booking->payment_status === 'paid') {
                $booking->update(['payment_status' => 'refunded']);
            }

            // Log activity
            ActivityHelper::activity()
                ->performedOn($booking)
                ->causedBy(auth()->user())
                ->log('Booking cancelled');

            return $booking->load(['user:id,name,email,phone', 'court.venue:id,name,address', 'court.sport:id,name']);
        });
    }

    /**
     * Confirm booking
     */
    public function confirmBooking(Booking $booking, ?string $notes = null): Booking
    {
        return DB::transaction(function () use ($booking, $notes) {
            if ($booking->status !== 'pending') {
                throw new \Exception('Chỉ có thể xác nhận booking ở trạng thái pending.');
            }

            $updateData = ['status' => 'confirmed'];
            if ($notes !== null) {
                $updateData['notes'] = $notes;
            }

            $booking->update($updateData);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($booking)
                ->causedBy(auth()->user())
                ->log('Booking confirmed');

            return $booking->load(['user:id,name,email,phone', 'court.venue:id,name,address', 'court.sport:id,name']);
        });
    }

    /**
     * Complete booking
     */
    public function completeBooking(Booking $booking, ?string $notes = null): Booking
    {
        return DB::transaction(function () use ($booking, $notes) {
            if (!in_array($booking->status, ['confirmed', 'paid'])) {
                throw new \Exception('Chỉ có thể hoàn thành booking ở trạng thái confirmed hoặc paid.');
            }

            $updateData = ['status' => 'completed'];
            if ($notes !== null) {
                $updateData['notes'] = $notes;
            }

            $booking->update($updateData);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($booking)
                ->causedBy(auth()->user())
                ->log('Booking completed');

            return $booking->load(['user:id,name,email,phone', 'court.venue:id,name,address', 'court.sport:id,name']);
        });
    }

    /**
     * Refund booking
     */
    public function refundBooking(Booking $booking, ?float $refundAmount = null, ?string $reason = null, ?string $notes = null): Booking
    {
        return DB::transaction(function () use ($booking, $refundAmount, $reason, $notes) {
            if ($booking->payment_status !== 'paid') {
                throw new \Exception('Chỉ có thể hoàn tiền cho booking đã thanh toán.');
            }

            $refundAmount = $refundAmount ?? $booking->final_amount;

            if ($refundAmount > $booking->final_amount) {
                throw new \Exception('Số tiền hoàn lại không được vượt quá số tiền đã thanh toán.');
            }

            $updateData = [
                'payment_status' => 'refunded',
            ];

            if ($reason !== null) {
                $updateData['cancellation_reason'] = $reason;
            }

            if ($notes !== null) {
                $updateData['notes'] = $notes;
            }

            // Store refund amount in metadata
            $metadata = $booking->metadata ?? [];
            $metadata['refund_amount'] = $refundAmount;
            $metadata['refunded_at'] = now()->toDateTimeString();
            $updateData['metadata'] = $metadata;

            $booking->update($updateData);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($booking)
                ->causedBy(auth()->user())
                ->log("Booking refunded: {$refundAmount}");

            return $booking->load(['user:id,name,email,phone', 'court.venue:id,name,address', 'court.sport:id,name']);
        });
    }

    /**
     * Send reminder for booking
     */
    public function remindBooking(Booking $booking, ?string $message = null): bool
    {
        // This would typically send a notification
        // For now, we'll just log it
        ActivityHelper::activity()
            ->performedOn($booking)
            ->causedBy(auth()->user())
            ->log('Booking reminder sent');

        // TODO: Implement actual notification sending via FCMService
        return true;
    }

    /**
     * Check if court is available for booking
     */
    private function isCourtAvailable(Court $court, string $date, string $startTime, string $endTime): bool
    {
        // Check if there's any overlapping booking
        $overlappingBookings = $court->bookings()
            ->where('booking_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // New booking starts before existing ends AND new booking ends after existing starts
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->exists();

        return !$overlappingBookings;
    }

    /**
     * Check if court is available excluding a specific booking
     */
    private function isCourtAvailableExcludingBooking(Court $court, string $date, string $startTime, string $endTime, int $excludeBookingId): bool
    {
        $overlappingBookings = $court->bookings()
            ->where('booking_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->where('id', '!=', $excludeBookingId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->exists();

        return !$overlappingBookings;
    }

    /**
     * Calculate booking price from time slots
     */
    private function calculateBookingPrice(Court $court, string $date, string $startTime, string $endTime): float
    {
        // Get time slots that overlap with booking time
        $timeSlots = TimeSlot::where('court_id', $court->id)
            ->where('date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->get();

        if ($timeSlots->isEmpty()) {
            // Fallback to hourly rate if no time slots found
            $start = Carbon::createFromTimeString($startTime);
            $end = Carbon::createFromTimeString($endTime);
            $hours = $end->diffInHours($start);
            return $court->hourly_rate * $hours;
        }

        // Calculate total price from overlapping time slots
        $totalPrice = 0;
        $bookingStart = Carbon::createFromTimeString($startTime);
        $bookingEnd = Carbon::createFromTimeString($endTime);

        foreach ($timeSlots as $slot) {
            $slotStart = Carbon::createFromTimeString($slot->start_time);
            $slotEnd = Carbon::createFromTimeString($slot->end_time);

            // Calculate overlap
            $overlapStart = max($bookingStart, $slotStart);
            $overlapEnd = min($bookingEnd, $slotEnd);

            if ($overlapStart < $overlapEnd) {
                $overlapMinutes = $overlapEnd->diffInMinutes($overlapStart);
                $slotDuration = $slotEnd->diffInMinutes($slotStart);
                $pricePerMinute = $slot->price / $slotDuration;
                $totalPrice += $pricePerMinute * $overlapMinutes;
            }
        }

        return round($totalPrice, 2);
    }
}
