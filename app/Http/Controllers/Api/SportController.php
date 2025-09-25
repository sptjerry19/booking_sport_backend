<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sport\StoreSportRequest;
use App\Http\Requests\Sport\UpdateSportRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Sport;
use App\Services\SportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SportController extends Controller
{
    public function __construct(
        private SportService $sportService
    ) {}

    /**
     * Display a listing of sports with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'is_active',
                'search',
                'min_players',
                'max_players',
                'sort_by',
                'sort_order',
                'per_page'
            ]);

            $sports = $this->sportService->getSports($filters);

            return ApiResponse::paginated($sports, 'Danh sách môn thể thao');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy danh sách môn thể thao: ' . $e->getMessage());
        }
    }

    /**
     * Get all active sports (for dropdown/select)
     */
    public function active(): JsonResponse
    {
        try {
            $sports = $this->sportService->getActiveSports();

            return ApiResponse::success($sports, 'Danh sách môn thể thao hoạt động');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy danh sách môn thể thao: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created sport
     */
    public function store(StoreSportRequest $request): JsonResponse
    {
        try {
            $sport = $this->sportService->createSport($request->getValidatedData());

            return ApiResponse::created($sport, 'Tạo môn thể thao thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi tạo môn thể thao: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified sport
     */
    public function show(int $id): JsonResponse
    {
        try {
            $sport = $this->sportService->getSportById($id, ['courts.venue:id,name']);

            if (!$sport) {
                return ApiResponse::notFound('Môn thể thao không tồn tại');
            }

            return ApiResponse::success($sport, 'Chi tiết môn thể thao');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy chi tiết môn thể thao: ' . $e->getMessage());
        }
    }

    /**
     * Get sport by slug
     */
    public function bySlug(string $slug): JsonResponse
    {
        try {
            $sport = $this->sportService->getSportBySlug($slug);

            if (!$sport) {
                return ApiResponse::notFound('Môn thể thao không tồn tại');
            }

            return ApiResponse::success($sport, 'Chi tiết môn thể thao');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy chi tiết môn thể thao: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified sport
     */
    public function update(UpdateSportRequest $request, int $id): JsonResponse
    {
        try {
            $sport = Sport::find($id);

            if (!$sport) {
                return ApiResponse::notFound('Môn thể thao không tồn tại');
            }

            $updatedSport = $this->sportService->updateSport($sport, $request->validated());

            return ApiResponse::updated($updatedSport, 'Cập nhật môn thể thao thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi cập nhật môn thể thao: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified sport
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $sport = Sport::find($id);

            if (!$sport) {
                return ApiResponse::notFound('Môn thể thao không tồn tại');
            }

            $this->sportService->deleteSport($sport);

            return ApiResponse::deleted('Xóa môn thể thao thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi xóa môn thể thao: ' . $e->getMessage());
        }
    }

    /**
     * Toggle sport status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $sport = Sport::find($id);

            if (!$sport) {
                return ApiResponse::notFound('Môn thể thao không tồn tại');
            }

            $updatedSport = $this->sportService->toggleSportStatus($sport);

            return ApiResponse::updated($updatedSport, 'Thay đổi trạng thái môn thể thao thành công');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi thay đổi trạng thái: ' . $e->getMessage());
        }
    }

    /**
     * Get sports with court count
     */
    public function withCourtCount(): JsonResponse
    {
        try {
            $sports = $this->sportService->getSportsWithCourtCount();

            return ApiResponse::success($sports, 'Danh sách môn thể thao với số lượng sân');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy thống kê: ' . $e->getMessage());
        }
    }

    /**
     * Get popular sports
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 8);
            $sports = $this->sportService->getPopularSports($limit);

            return ApiResponse::success($sports, 'Danh sách môn thể thao phổ biến');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy môn thể thao phổ biến: ' . $e->getMessage());
        }
    }

    /**
     * Get sport statistics
     */
    public function statistics(int $id, Request $request): JsonResponse
    {
        try {
            $sport = Sport::find($id);

            if (!$sport) {
                return ApiResponse::notFound('Môn thể thao không tồn tại');
            }

            $filters = $request->only(['start_date', 'end_date']);
            $statistics = $this->sportService->getSportStatistics($sport, $filters);

            return ApiResponse::success($statistics, 'Thống kê môn thể thao');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi lấy thống kê: ' . $e->getMessage());
        }
    }

    /**
     * Search sports by position
     */
    public function searchByPosition(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'position' => 'required|string'
            ]);

            $sports = $this->sportService->searchSportsByPosition($request->position);

            return ApiResponse::success($sports, "Danh sách môn thể thao có vị trí '{$request->position}'");
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi tìm kiếm: ' . $e->getMessage());
        }
    }

    /**
     * Get sports suitable for player count
     */
    public function forPlayerCount(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'player_count' => 'required|integer|min:1'
            ]);

            $sports = $this->sportService->getSportsForPlayerCount($request->player_count);

            return ApiResponse::success($sports, "Danh sách môn thể thao phù hợp cho {$request->player_count} người");
        } catch (\Exception $e) {
            return ApiResponse::serverError('Lỗi khi tìm kiếm: ' . $e->getMessage());
        }
    }
}
