<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Court\StoreCourtRequest;
use App\Http\Requests\Court\UpdateCourtRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Court;
use App\Models\Venue;
use App\Services\CourtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    public function __construct(
        private CourtService $courtService
    ) {}

    /**
     * Display a listing of courts with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'venue_id',
                'sport_id',
                'surface_type',
                'min_price',
                'max_price',
                'search',
                'sort_by',
                'sort_order',
                'per_page'
            ]);

            $courts = $this->courtService->getCourts($filters);

            return ApiResponse::paginated($courts, 'Danh sách sân');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy danh sách sân: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created court
     */
    public function store(StoreCourtRequest $request): JsonResponse
    {
        try {
            $court = $this->courtService->createCourt($request->getValidatedData());

            return ApiResponse::created($court, 'Tạo sân thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi tạo sân: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified court
     */
    public function show(int $id): JsonResponse
    {
        try {
            $court = $this->courtService->getCourtById($id, ['pricingRules']);

            if (!$court) {
                return ApiResponse::notFound('Sân không tồn tại');
            }

            return ApiResponse::success($court, 'Chi tiết sân');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy chi tiết sân: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified court
     */
    public function update(UpdateCourtRequest $request, int $id): JsonResponse
    {
        try {
            $court = Court::find($id);

            if (!$court) {
                return ApiResponse::notFound('Sân không tồn tại');
            }

            // Check ownership or admin permission
            if ($court->venue->owner_id !== auth()->id() && auth()->user()->email !== 'admin@example.com') {
                return ApiResponse::forbidden('Bạn không có quyền chỉnh sửa sân này');
            }

            $updatedCourt = $this->courtService->updateCourt($court, $request->validated());

            return ApiResponse::updated($updatedCourt, 'Cập nhật sân thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi cập nhật sân: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified court
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $court = Court::find($id);

            if (!$court) {
                return ApiResponse::notFound('Sân không tồn tại');
            }

            // Check ownership or admin permission
            if ($court->venue->owner_id !== auth()->id() && auth()->user()->email !== 'admin@example.com') {
                return ApiResponse::forbidden('Bạn không có quyền xóa sân này');
            }

            $this->courtService->deleteCourt($court);

            return ApiResponse::deleted('Xóa sân thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi xóa sân: ' . $e->getMessage());
        }
    }

    /**
     * Get courts by venue
     */
    public function byVenue(int $venueId): JsonResponse
    {
        try {
            $venue = Venue::find($venueId);

            if (!$venue) {
                return ApiResponse::notFound('Venue không tồn tại');
            }

            $courts = $this->courtService->getCourtsByVenue($venue);

            return ApiResponse::success($courts, 'Danh sách sân của venue');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy danh sách sân: ' . $e->getMessage());
        }
    }

    /**
     * Toggle court status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $court = Court::find($id);

            if (!$court) {
                return ApiResponse::notFound('Sân không tồn tại');
            }

            // Check ownership or admin permission
            if ($court->venue->owner_id !== auth()->id() && auth()->user()->email !== 'admin@example.com') {
                return ApiResponse::forbidden('Bạn không có quyền thay đổi trạng thái sân này');
            }

            $updatedCourt = $this->courtService->toggleCourtStatus($court);

            return ApiResponse::updated($updatedCourt, 'Thay đổi trạng thái sân thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi thay đổi trạng thái: ' . $e->getMessage());
        }
    }

    /**
     * Get court availability for a specific date
     */
    public function availability(int $id, Request $request): JsonResponse
    {
        try {
            $court = Court::find($id);

            if (!$court) {
                return ApiResponse::notFound('Sân không tồn tại');
            }

            $date = $request->get('date', now()->format('Y-m-d'));

            $availability = $this->courtService->getCourtAvailability($court, $date);

            return ApiResponse::success($availability, "Thông tin trống của sân ngày {$date}");
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy thông tin trống: ' . $e->getMessage());
        }
    }

    /**
     * Get court availability for date range
     */
    public function availabilityRange(int $id, Request $request): JsonResponse
    {
        try {
            $court = Court::find($id);

            if (!$court) {
                return ApiResponse::notFound('Sân không tồn tại');
            }

            $startDate = $request->get('start_date', now()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->addDays(7)->format('Y-m-d'));

            $availability = $this->courtService->getCourtAvailabilityRange($court, $startDate, $endDate);

            return ApiResponse::success($availability, "Thông tin trống của sân từ {$startDate} đến {$endDate}");
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy thông tin trống: ' . $e->getMessage());
        }
    }

    /**
     * Check if court is available for booking
     */
    public function checkAvailability(int $id, Request $request): JsonResponse
    {
        try {
            $court = Court::find($id);

            if (!$court) {
                return ApiResponse::notFound('Sân không tồn tại');
            }

            $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
            ]);

            $isAvailable = $this->courtService->isCourtAvailable(
                $court,
                $request->date,
                $request->start_time,
                $request->end_time
            );

            return ApiResponse::success(
                ['is_available' => $isAvailable],
                $isAvailable ? 'Sân có thể đặt' : 'Sân đã được đặt trong khung giờ này'
            );
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi kiểm tra tình trạng: ' . $e->getMessage());
        }
    }

    /**
     * Get court statistics
     */
    public function statistics(int $id, Request $request): JsonResponse
    {
        try {
            $court = Court::find($id);

            if (!$court) {
                return ApiResponse::notFound('Sân không tồn tại');
            }

            // Check ownership or admin permission
            if ($court->venue->owner_id !== auth()->id() && auth()->user()->email !== 'admin@example.com') {
                return ApiResponse::forbidden('Bạn không có quyền xem thống kê sân này');
            }

            $filters = $request->only(['start_date', 'end_date']);
            $statistics = $this->courtService->getCourtStatistics($court, $filters);

            return ApiResponse::success($statistics, 'Thống kê sân');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy thống kê: ' . $e->getMessage());
        }
    }

    /**
     * Get popular courts
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $courts = $this->courtService->getPopularCourts($limit);

            return ApiResponse::success($courts, 'Danh sách sân phổ biến');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy sân phổ biến: ' . $e->getMessage());
        }
    }
}
