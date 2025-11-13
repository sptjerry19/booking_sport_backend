<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venue\StoreVenueRequest;
use App\Http\Requests\Venue\UpdateVenueRequest;
use App\Http\Resources\VenueResource;
use App\Http\Responses\ApiResponse;
use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function __construct(
        private VenueService $venueService
    ) {}

    /**
     * Display a listing of venues with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'latitude',
                'longitude',
                'radius',
                'sport_id',
                'search',
                'amenities',
                'sort',
                'sort_order',
                'per_page',
                'location',
                'price_min',
                'price_max',
            ]);

            $venues = $this->venueService->getVenues($filters);

            return ApiResponse::paginated(VenueResource::collection($venues), 'Danh sách venues');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy danh sách venues: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created venue
     */
    public function store(StoreVenueRequest $request): JsonResponse
    {
        try {
            $venue = $this->venueService->createVenue($request->getValidatedData());

            return ApiResponse::created(new VenueResource($venue), 'Tạo venue thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi tạo venue: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified venue
     */
    public function show(int $id): JsonResponse
    {
        try {
            $venue = $this->venueService->getVenueById($id, ['courts.sport']);

            if (!$venue) {
                return ApiResponse::notFound('Venue không tồn tại');
            }

            return ApiResponse::success(new VenueResource($venue), 'Chi tiết venue');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy chi tiết venue: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified venue
     */
    public function update(UpdateVenueRequest $request, int $id): JsonResponse
    {
        try {
            $venue = Venue::find($id);

            if (!$venue) {
                return ApiResponse::notFound('Venue không tồn tại');
            }

            // Check ownership or admin permission
            if ($venue->owner_id !== auth()->id() && auth()->user()->email !== 'admin@example.com') {
                return ApiResponse::forbidden('Bạn không có quyền chỉnh sửa venue này');
            }

            $updatedVenue = $this->venueService->updateVenue($venue, $request->validated());

            return ApiResponse::updated(new VenueResource($updatedVenue), 'Cập nhật venue thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi cập nhật venue: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified venue
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $venue = Venue::find($id);

            if (!$venue) {
                return ApiResponse::notFound('Venue không tồn tại');
            }

            // Check ownership or admin permission
            if ($venue->owner_id !== auth()->id() && auth()->user()->email !== 'admin@example.com') {
                return ApiResponse::forbidden('Bạn không có quyền xóa venue này');
            }

            $this->venueService->deleteVenue($venue);

            return ApiResponse::deleted('Xóa venue thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi xóa venue: ' . $e->getMessage());
        }
    }

    /**
     * Get venues by owner
     */
    public function myVenues(): JsonResponse
    {
        try {
            $venues = $this->venueService->getVenuesByOwner(auth()->user());

            return ApiResponse::success(VenueResource::collection($venues), 'Danh sách venues của bạn');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy venues: ' . $e->getMessage());
        }
    }

    /**
     * Toggle venue status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $venue = Venue::find($id);

            if (!$venue) {
                return ApiResponse::notFound('Venue không tồn tại');
            }

            // Check ownership or admin permission
            if ($venue->owner_id !== auth()->id() && auth()->user()->email !== 'admin@example.com') {
                return ApiResponse::forbidden('Bạn không có quyền thay đổi trạng thái venue này');
            }

            $updatedVenue = $this->venueService->toggleVenueStatus($venue);

            return ApiResponse::updated(new VenueResource($updatedVenue), 'Thay đổi trạng thái venue thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi thay đổi trạng thái: ' . $e->getMessage());
        }
    }

    /**
     * Get venue availability for a specific date
     */
    public function availability(int $id, Request $request): JsonResponse
    {
        try {
            $venue = Venue::find($id);

            if (!$venue) {
                return ApiResponse::notFound('Venue không tồn tại');
            }

            $date = $request->get('date', now()->format('Y-m-d'));

            $availability = $this->venueService->getVenueAvailability($venue, $date);

            return ApiResponse::success($availability, "Thông tin trống của venue ngày {$date}");
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy thông tin trống: ' . $e->getMessage());
        }
    }

    /**
     * Get venue statistics
     */
    public function statistics(int $id, Request $request): JsonResponse
    {
        try {
            $venue = Venue::find($id);

            if (!$venue) {
                return ApiResponse::notFound('Venue không tồn tại');
            }

            // Check ownership or admin permission
            if ($venue->owner_id !== auth()->id() && auth()->user()->email !== 'admin@example.com') {
                return ApiResponse::forbidden('Bạn không có quyền xem thống kê venue này');
            }

            $filters = $request->only(['start_date', 'end_date']);
            $statistics = $this->venueService->getVenueStatistics($venue, $filters);

            return ApiResponse::success($statistics, 'Thống kê venue');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy thống kê: ' . $e->getMessage());
        }
    }
}