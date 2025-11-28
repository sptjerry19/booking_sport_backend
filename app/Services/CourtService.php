<?php

namespace App\Services;

use App\Models\Court;
use App\Models\Venue;
use App\Helpers\ActivityHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CourtService
{
    /**
     * Get courts with filtering and pagination
     */
    public function getCourts(array $filters = []): LengthAwarePaginator
    {
        $query = Court::with(['venue:id,name,address', 'sport:id,name'])
            ->active();

        // Filter by venue
        if (isset($filters['venue_id'])) {
            $query->where('venue_id', $filters['venue_id']);
        }

        // Filter by sport
        if (isset($filters['sport_id'])) {
            $query->where('sport_id', $filters['sport_id']);
        }

        // Filter by surface type
        if (isset($filters['surface_type'])) {
            $query->where('surface_type', $filters['surface_type']);
        }

        // Filter by price range
        if (isset($filters['min_price'])) {
            $query->where('hourly_rate', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('hourly_rate', '<=', $filters['max_price']);
        }

        // Search by name or code
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        switch ($sortBy) {
            case 'hourly_rate':
                $query->orderBy('hourly_rate', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'name':
            default:
                $query->orderBy('name', $sortOrder);
                break;
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get court by ID with relationships
     */
    public function getCourtById(int $courtId, array $relations = []): ?Court
    {
        $defaultRelations = ['venue:id,name,address,opening_time,closing_time', 'sport:id,name'];
        $relations = array_merge($defaultRelations, $relations);

        return Court::with($relations)->find($courtId);
    }

    /**
     * Get courts by venue
     */
    public function getCourtsByVenue(Venue $venue): Collection
    {
        return Court::with(['sport:id,name'])
            ->where('venue_id', $venue->id)
            ->active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Create new court
     */
    public function createCourt(array $data): Court
    {
        return DB::transaction(function () use ($data) {
            // Handle image uploads
            if (isset($data['images'])) {
                $data['images'] = $this->handleImageUploads($data['images']);
            }

            $court = Court::create($data);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($court)
                ->causedBy(auth()->user())
                ->log('Court created');

            return $court->load(['venue:id,name', 'sport:id,name']);
        });
    }

    /**
     * Update court
     */
    public function updateCourt(Court $court, array $data): Court
    {
        return DB::transaction(function () use ($court, $data) {
            // Handle image uploads
            if (isset($data['images'])) {
                // Delete old images if new ones are uploaded
                if ($court->images) {
                    $this->deleteImages($court->images);
                }
                $data['images'] = $this->handleImageUploads($data['images']);
            }

            $court->update($data);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($court)
                ->causedBy(auth()->user())
                ->log('Court updated');

            return $court->load(['venue:id,name', 'sport:id,name']);
        });
    }

    /**
     * Delete court
     */
    public function deleteCourt(Court $court): bool
    {
        return DB::transaction(function () use ($court) {
            // Check if court has active bookings
            $hasActiveBookings = $court->bookings()
                ->where('booking_date', '>=', now()->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->exists();

            if ($hasActiveBookings) {
                throw new \Exception('Không thể xóa sân có booking đang hoạt động.');
            }

            // Delete images
            if ($court->images) {
                $this->deleteImages($court->images);
            }

            // Log activity before deletion
            ActivityHelper::activity()
                ->performedOn($court)
                ->causedBy(auth()->user())
                ->log('Court deleted');

            return $court->delete();
        });
    }

    /**
     * Toggle court status
     */
    public function toggleCourtStatus(Court $court): Court
    {
        $court->update(['is_active' => !$court->is_active]);

        $status = $court->is_active ? 'activated' : 'deactivated';

        ActivityHelper::activity()
            ->performedOn($court)
            ->causedBy(auth()->user())
            ->log("Court {$status}");

        return $court;
    }

    /**
     * Get court availability for a specific date
     */
    public function getCourtAvailability(Court $court, string $date): array
    {
        $timeSlots = $court->timeSlots()
            ->where('date', $date)
            ->where('is_available', true)
            ->orderBy('start_time')
            ->get();

        return $timeSlots->map(function ($slot) {
            return [
                'slot_id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'price' => $slot->price,
                'is_available' => $slot->is_available,
            ];
        })->toArray();
    }

    /**
     * Get court availability for date range
     */
    public function getCourtAvailabilityRange(Court $court, string $startDate, string $endDate): array
    {
        $availability = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($start->lte($end)) {
            $dateStr = $start->format('Y-m-d');
            $availability[$dateStr] = $this->getCourtAvailability($court, $dateStr);
            $start->addDay();
        }

        return $availability;
    }

    /**
     * Check if court is available for booking
     */
    public function isCourtAvailable(Court $court, string $date, string $startTime, string $endTime): bool
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
     * Get court statistics
     */
    public function getCourtStatistics(Court $court, array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth();
        $endDate = $filters['end_date'] ?? now()->endOfMonth();

        $bookingsQuery = $court->bookings()
            ->whereBetween('booking_date', [$startDate, $endDate]);

        return [
            'total_bookings' => $bookingsQuery->count(),
            'total_revenue' => $bookingsQuery->sum('final_amount'),
            'confirmed_bookings' => $bookingsQuery->where('status', 'confirmed')->count(),
            'cancelled_bookings' => $bookingsQuery->where('status', 'cancelled')->count(),
            'average_booking_duration' => $this->getAverageBookingDuration($court, $startDate, $endDate),
            'peak_hours' => $this->getPeakHours($court, $startDate, $endDate),
        ];
    }

    /**
     * Get popular courts by booking count
     */
    public function getPopularCourts(int $limit = 10): Collection
    {
        return Court::with(['venue:id,name', 'sport:id,name'])
            ->withCount(['bookings' => function ($query) {
                $query->whereBetween('booking_date', [now()->subMonth(), now()])
                    ->whereNotIn('status', ['cancelled']);
            }])
            ->orderBy('bookings_count', 'desc')
            ->active()
            ->limit($limit)
            ->get();
    }

    /**
     * Handle image uploads
     */
    private function handleImageUploads(array $images): array
    {
        $uploadedImages = [];

        foreach ($images as $image) {
            if ($image->isValid()) {
                $path = $image->store('courts', 'public');
                $uploadedImages[] = $path;
            }
        }

        return $uploadedImages;
    }

    /**
     * Delete images from storage
     */
    private function deleteImages(array $images): void
    {
        foreach ($images as $image) {
            Storage::disk('public')->delete($image);
        }
    }

    /**
     * Get average booking duration
     */
    private function getAverageBookingDuration(Court $court, $startDate, $endDate): float
    {
        $bookings = $court->bookings()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->get();

        if ($bookings->isEmpty()) {
            return 0;
        }

        $totalDuration = $bookings->sum(function ($booking) {
            $start = Carbon::createFromTimeString($booking->start_time);
            $end = Carbon::createFromTimeString($booking->end_time);
            return $end->diffInMinutes($start);
        });

        return $totalDuration / $bookings->count();
    }

    /**
     * Get peak hours analysis
     */
    private function getPeakHours(Court $court, $startDate, $endDate): array
    {
        $bookings = $court->bookings()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->groupBy(function ($booking) {
                return Carbon::createFromTimeString($booking->start_time)->format('H');
            })
            ->map->count()
            ->sortDesc();

        return $bookings->toArray();
    }
}
