<?php

namespace App\Services;

use App\Models\Sport;
use App\Helpers\ActivityHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SportService
{
    /**
     * Get sports with filtering and pagination
     */
    public function getSports(array $filters = []): LengthAwarePaginator
    {
        $query = Sport::query();

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        } else {
            $query->active(); // Default to active sports only
        }

        // Search by name
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by player count
        if (isset($filters['min_players'])) {
            $query->where('min_players', '<=', $filters['min_players']);
        }

        if (isset($filters['max_players'])) {
            $query->where('max_players', '>=', $filters['max_players']);
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        switch ($sortBy) {
            case 'min_players':
            case 'max_players':
                $query->orderBy($sortBy, $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'popularity':
                $query->withCount('courts')
                    ->orderBy('courts_count', $sortOrder);
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
     * Get all active sports (for dropdown/select options)
     */
    public function getActiveSports(): Collection
    {
        return Sport::active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon', 'min_players', 'max_players']);
    }

    /**
     * Get sport by ID with relationships
     */
    public function getSportById(int $sportId, array $relations = []): ?Sport
    {
        $query = Sport::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($sportId);
    }

    /**
     * Get sport by slug
     */
    public function getSportBySlug(string $slug): ?Sport
    {
        return Sport::where('slug', $slug)->first();
    }

    /**
     * Create new sport
     */
    public function createSport(array $data): Sport
    {
        return DB::transaction(function () use ($data) {
            $sport = Sport::create($data);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($sport)
                ->causedBy(auth()->user())
                ->log('Sport created');

            return $sport;
        });
    }

    /**
     * Update sport
     */
    public function updateSport(Sport $sport, array $data): Sport
    {
        return DB::transaction(function () use ($sport, $data) {
            $sport->update($data);

            // Log activity
            ActivityHelper::activity()
                ->performedOn($sport)
                ->causedBy(auth()->user())
                ->log('Sport updated');

            return $sport;
        });
    }

    /**
     * Delete sport
     */
    public function deleteSport(Sport $sport): bool
    {
        return DB::transaction(function () use ($sport) {
            // Check if sport has courts
            if ($sport->courts()->exists()) {
                throw new \Exception('Không thể xóa môn thể thao có sân đang sử dụng.');
            }

            // Log activity before deletion
            ActivityHelper::activity()
                ->performedOn($sport)
                ->causedBy(auth()->user())
                ->log('Sport deleted');

            return $sport->delete();
        });
    }

    /**
     * Toggle sport status
     */
    public function toggleSportStatus(Sport $sport): Sport
    {
        $sport->update(['is_active' => !$sport->is_active]);

        $status = $sport->is_active ? 'activated' : 'deactivated';

        ActivityHelper::activity()
            ->performedOn($sport)
            ->causedBy(auth()->user())
            ->log("Sport {$status}");

        return $sport;
    }

    /**
     * Get sports with court count
     */
    public function getSportsWithCourtCount(): Collection
    {
        return Sport::active()
            ->withCount('courts')
            ->orderBy('courts_count', 'desc')
            ->get();
    }

    /**
     * Get popular sports by booking count
     */
    public function getPopularSports(int $limit = 10): Collection
    {
        return Sport::active()
            ->withCount(['courts as bookings_count' => function ($query) {
                $query->join('bookings', 'courts.id', '=', 'bookings.court_id')
                    ->whereBetween('bookings.booking_date', [now()->subMonth(), now()])
                    ->whereNotIn('bookings.status', ['cancelled']);
            }])
            ->orderBy('bookings_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sport statistics
     */
    public function getSportStatistics(Sport $sport, array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth();
        $endDate = $filters['end_date'] ?? now()->endOfMonth();

        $courtsQuery = $sport->courts()->active();

        $bookingsQuery = DB::table('bookings')
            ->join('courts', 'bookings.court_id', '=', 'courts.id')
            ->where('courts.sport_id', $sport->id)
            ->whereBetween('bookings.booking_date', [$startDate, $endDate]);

        return [
            'total_courts' => $courtsQuery->count(),
            'active_venues' => $courtsQuery->distinct('venue_id')->count(),
            'total_bookings' => $bookingsQuery->count(),
            'total_revenue' => $bookingsQuery->sum('bookings.final_amount'),
            'confirmed_bookings' => $bookingsQuery->where('bookings.status', 'confirmed')->count(),
            'cancelled_bookings' => $bookingsQuery->where('bookings.status', 'cancelled')->count(),
            'average_booking_value' => $this->getAverageBookingValue($sport, $startDate, $endDate),
            'popular_time_slots' => $this->getPopularTimeSlots($sport, $startDate, $endDate),
        ];
    }

    /**
     * Search sports by position
     */
    public function searchSportsByPosition(string $position): Collection
    {
        return Sport::active()
            ->whereJsonContains('positions', $position)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get sports suitable for player count
     */
    public function getSportsForPlayerCount(int $playerCount): Collection
    {
        return Sport::active()
            ->where('min_players', '<=', $playerCount)
            ->where('max_players', '>=', $playerCount)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get average booking value for sport
     */
    private function getAverageBookingValue(Sport $sport, $startDate, $endDate): float
    {
        $result = DB::table('bookings')
            ->join('courts', 'bookings.court_id', '=', 'courts.id')
            ->where('courts.sport_id', $sport->id)
            ->whereBetween('bookings.booking_date', [$startDate, $endDate])
            ->whereNotIn('bookings.status', ['cancelled'])
            ->avg('bookings.final_amount');

        return $result ?? 0;
    }

    /**
     * Get popular time slots for sport
     */
    private function getPopularTimeSlots(Sport $sport, $startDate, $endDate): array
    {
        $results = DB::table('bookings')
            ->join('courts', 'bookings.court_id', '=', 'courts.id')
            ->where('courts.sport_id', $sport->id)
            ->whereBetween('bookings.booking_date', [$startDate, $endDate])
            ->whereNotIn('bookings.status', ['cancelled'])
            ->select(
                DB::raw('HOUR(bookings.start_time) as hour'),
                DB::raw('COUNT(*) as booking_count')
            )
            ->groupBy('hour')
            ->orderBy('booking_count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        return array_map(function ($result) {
            return [
                'hour' => $result->hour,
                'booking_count' => $result->booking_count,
                'time_slot' => sprintf('%02d:00 - %02d:00', $result->hour, $result->hour + 1)
            ];
        }, $results);
    }
}
