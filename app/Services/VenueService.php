<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\User;
use App\Helpers\ActivityHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VenueService
{
    /**
     * Get venues with filtering and pagination
     */
    public function getVenues(array $filters = []): LengthAwarePaginator
    {
        $query = Venue::with(['owner', 'courts.sport'])
            ->active();  // Đây là query scope

        // Filter by location (nearby venues)
        if (isset($filters['latitude']) && isset($filters['longitude'])) {
            $radius = $filters['radius'] ?? 10; // Default 10km
            $query->nearby($filters['latitude'], $filters['longitude'], $radius);
        }

        // Filter by sport (venues that have courts for specific sport)
        if (isset($filters['sport_id'])) {
            $query->whereHas('courts', function ($q) use ($filters) {
                $q->where('sport_id', $filters['sport_id'])
                    ->where('is_active', true);
            });
        }

        // Search by name or address
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by amenities
        if (isset($filters['amenities']) && is_array($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        // Filter by price range (based on courts' hourly_rate)
        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $query->whereHas('courts', function ($q) use ($filters) {
                if (isset($filters['price_min'])) {
                    $q->where('hourly_rate', '>=', $filters['price_min']);
                }
                if (isset($filters['price_max'])) {
                    $q->where('hourly_rate', '<=', $filters['price_max']);
                }
            });
        }

        // Filter by location keyword
        if (isset($filters['location'])) {
            $location = $filters['location'];
            $query->where('address', 'like', "%{$location}%");
        }

        // Sort options
        $sortBy = $filters['sort'] ?? 'distance';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'distance':
            default:
                // Distance ordering is already applied in nearby scope
                if (!isset($filters['latitude'])) {
                    $query->orderBy('name', 'asc');
                }
                break;
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get venue by ID with relationships
     */
    public function getVenueById(int $venueId, array $relations = []): ?Venue
    {
        $defaultRelations = ['owner', 'courts.sport'];
        $relations = array_merge($defaultRelations, $relations);

        return Venue::with($relations)->find($venueId);
    }

    /**
     * Create new venue
     */
    public function createVenue(array $data): Venue
    {
        return DB::transaction(function () use ($data) {
            // Handle image uploads
            if (isset($data['images'])) {
                $data['images'] = $this->handleImageUploads($data['images']);
            }

            $venue = Venue::create($data);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($venue)
                ->causedBy($venue->owner)
                ->log('Venue created');

            return $venue->load('owner:id,name,email');
        });
    }

    /**
     * Update venue
     */
    public function updateVenue(Venue $venue, array $data): Venue
    {
        return DB::transaction(function () use ($venue, $data) {
            // Handle image uploads
            if (isset($data['images'])) {
                // Delete old images if new ones are uploaded
                if ($venue->images) {
                    $this->deleteImages($venue->images);
                }
                $data['images'] = $this->handleImageUploads($data['images']);
            }

            $venue->update($data);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($venue)
                ->causedBy(auth()->user())
                ->log('Venue updated');

            return $venue->load('owner:id,name,email');
        });
    }

    /**
     * Delete venue
     */
    public function deleteVenue(Venue $venue): bool
    {
        return DB::transaction(function () use ($venue) {
            // Check if venue has active bookings
            $hasActiveBookings = $venue->courts()
                ->whereHas('bookings', function ($query) {
                    $query->where('booking_date', '>=', now()->toDateString())
                        ->whereNotIn('status', ['cancelled']);
                })
                ->exists();

            if ($hasActiveBookings) {
                throw new \Exception('Không thể xóa venue có booking đang hoạt động.');
            }

            // Delete images
            if ($venue->images) {
                $this->deleteImages($venue->images);
            }

            // Log activity before deletion
            ActivityHelper::activity()
                ->performedOn($venue)
                ->causedBy(auth()->user())
                ->log('Venue deleted');

            return $venue->delete();
        });
    }

    /**
     * Get venues by owner
     */
    public function getVenuesByOwner(User $owner): Collection
    {
        return Venue::where('owner_id', $owner->id)
            ->with(['courts' => function ($query) {
                $query->withCount('bookings');
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Toggle venue status
     */
    public function toggleVenueStatus(Venue $venue): Venue
    {
        $newStatus = $venue->status === 'active' ? 'inactive' : 'active';

        $venue->update(['status' => $newStatus]);

        ActivityHelper::activity()
            ->performedOn($venue)
            ->causedBy(auth()->user())
            ->log("Venue status changed to {$newStatus}");

        return $venue;
    }

    /**
     * Get venue availability for a specific date
     */
    public function getVenueAvailability(Venue $venue, string $date): array
    {
        $courts = $venue->courts()
            ->with(['timeSlots' => function ($query) use ($date) {
                $query->where('date', $date)
                    ->where('is_available', true)
                    ->orderBy('start_time');
            }])
            ->active()
            ->get();

        return $courts->map(function ($court) {
            return [
                'court_id' => $court->id,
                'court_name' => $court->name,
                'sport' => $court->sport->name,
                'hourly_rate' => $court->hourly_rate,
                'available_slots' => $court->timeSlots->map(function ($slot) {
                    return [
                        'slot_id' => $slot->id,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'price' => $slot->price,
                    ];
                })
            ];
        })->toArray();
    }

    /**
     * Handle image uploads
     */
    private function handleImageUploads(array $images): array
    {
        $uploadedImages = [];
        $disk = config('filesystems.default', 'public');

        foreach ($images as $image) {
            if ($image->isValid()) {
                // Sử dụng disk được cấu hình (có thể là s3, public, etc.)
                $path = $image->store('venues', $disk);
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
        $disk = config('filesystems.default', 'public');

        foreach ($images as $image) {
            // Chỉ xóa nếu là path trong storage, không xóa URL external
            if (!filter_var($image, FILTER_VALIDATE_URL)) {
                Storage::disk($disk)->delete($image);
            }
        }
    }

    /**
     * Get venue statistics
     */
    public function getVenueStatistics(Venue $venue, array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth();
        $endDate = $filters['end_date'] ?? now()->endOfMonth();

        $bookingsQuery = $venue->courts()
            ->join('bookings', 'courts.id', '=', 'bookings.court_id')
            ->whereBetween('bookings.booking_date', [$startDate, $endDate]);

        return [
            'total_bookings' => $bookingsQuery->count(),
            'total_revenue' => $bookingsQuery->sum('bookings.final_amount'),
            'confirmed_bookings' => $bookingsQuery->where('bookings.status', 'confirmed')->count(),
            'cancelled_bookings' => $bookingsQuery->where('bookings.status', 'cancelled')->count(),
            'occupancy_rate' => $this->calculateOccupancyRate($venue, $startDate, $endDate),
        ];
    }

    /**
     * Calculate occupancy rate
     */
    private function calculateOccupancyRate(Venue $venue, $startDate, $endDate): float
    {
        $totalSlots = $venue->courts()
            ->join('time_slots', 'courts.id', '=', 'time_slots.court_id')
            ->whereBetween('time_slots.date', [$startDate, $endDate])
            ->count();

        $bookedSlots = $venue->courts()
            ->join('bookings', 'courts.id', '=', 'bookings.court_id')
            ->whereBetween('bookings.booking_date', [$startDate, $endDate])
            ->whereNotIn('bookings.status', ['cancelled'])
            ->count();

        return $totalSlots > 0 ? ($bookedSlots / $totalSlots) * 100 : 0;
    }
}
