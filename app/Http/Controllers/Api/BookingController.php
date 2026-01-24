<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\ConfirmBookingRequest;
use App\Http\Requests\Booking\CompleteBookingRequest;
use App\Http\Requests\Booking\RefundBookingRequest;
use App\Http\Requests\Booking\RemindBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Check if user is admin or owner
     */
    private function isAdminOrOwner($user): bool
    {
        /** @var \App\Models\User $user */
        return $user->hasRole('admin') || $user->hasRole('owner');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'user_id',
            'court_id',
            'status',
            'payment_status',
            'start_date',
            'end_date',
            'booking_date_from',
            'booking_date_to',
            'search',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        // If user is not admin/owner, only show their own bookings
        $user = auth()->user();
        if (!$this->isAdminOrOwner($user)) {
            $filters['user_id'] = auth()->id();
        }

        $bookings = $this->bookingService->getBookings($filters);

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $booking = $this->bookingService->createBooking($data);

            return response()->json([
                'success' => true,
                'message' => 'Đặt sân thành công.',
                'data' => new BookingResource($booking),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking): JsonResponse
    {
        // Check authorization - users can only view their own bookings unless admin/owner
        $user = auth()->user();
        if (!$this->isAdminOrOwner($user) && $booking->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền truy cập.',
            ], 403);
        }

        $booking = $this->bookingService->getBookingById($booking->id);

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        try {
            // Check authorization
            $user = auth()->user();
            if (!$this->isAdminOrOwner($user) && $booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền cập nhật.',
                ], 403);
            }

            $data = $request->validated();
            $booking = $this->bookingService->updateBooking($booking, $data);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật booking thành công.',
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking): JsonResponse
    {
        try {
            // Check authorization
            $user = auth()->user();
            if (!$this->isAdminOrOwner($user) && $booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền xóa.',
                ], 403);
            }

            $this->bookingService->deleteBooking($booking);

            return response()->json([
                'success' => true,
                'message' => 'Xóa booking thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel booking
     */
    public function cancel(CancelBookingRequest $request, Booking $booking): JsonResponse
    {
        try {
            // Check authorization
            $user = auth()->user();
            if (!$this->isAdminOrOwner($user) && $booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền hủy booking.',
                ], 403);
            }

            $data = $request->validated();
            $booking = $this->bookingService->cancelBooking($booking, $data['cancellation_reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Hủy booking thành công.',
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm booking
     */
    public function confirm(ConfirmBookingRequest $request, Booking $booking): JsonResponse
    {
        try {
            // Only admin/owner can confirm bookings
            $user = auth()->user();
            if (!$this->isAdminOrOwner($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ admin/owner mới có quyền xác nhận booking.',
                ], 403);
            }

            $data = $request->validated();
            $booking = $this->bookingService->confirmBooking($booking, $data['notes'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Xác nhận booking thành công.',
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete booking
     */
    public function complete(CompleteBookingRequest $request, Booking $booking): JsonResponse
    {
        try {
            // Only admin/owner can complete bookings
            $user = auth()->user();
            if (!$this->isAdminOrOwner($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ admin/owner mới có quyền hoàn thành booking.',
                ], 403);
            }

            $data = $request->validated();
            $booking = $this->bookingService->completeBooking($booking, $data['notes'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Hoàn thành booking thành công.',
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Refund booking
     */
    public function refund(RefundBookingRequest $request, Booking $booking): JsonResponse
    {
        try {
            // Only admin/owner can refund bookings
            $user = auth()->user();
            if (!$this->isAdminOrOwner($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ admin/owner mới có quyền hoàn tiền.',
                ], 403);
            }

            $data = $request->validated();
            $booking = $this->bookingService->refundBooking(
                $booking,
                $data['refund_amount'] ?? null,
                $data['refund_reason'] ?? null,
                $data['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Hoàn tiền thành công.',
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Send reminder for booking
     */
    public function remind(RemindBookingRequest $request, Booking $booking): JsonResponse
    {
        try {
            // Only admin/owner can send reminders
            $user = auth()->user();
            if (!$this->isAdminOrOwner($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ admin/owner mới có quyền gửi nhắc nhở.',
                ], 403);
            }

            $data = $request->validated();
            $this->bookingService->remindBooking($booking, $data['message'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Gửi nhắc nhở thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
