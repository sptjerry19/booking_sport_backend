<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Intervention\Image\Facades\Image;

class ProfileController extends Controller
{
    /**
     * Lấy thông tin profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'level' => $user->level,
                    'preferred_sports' => $user->preferred_sports,
                    'preferred_position' => $user->preferred_position,
                    'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'stats' => [
                    'total_bookings' => $user->bookings()->count() ?? 0,
                    'active_devices' => $user->activeDeviceTokens()->count(),
                ],
            ],
        ]);
    }

    /**
     * Cập nhật profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'level' => 'nullable|string|in:beginner,intermediate,advanced',
            'preferred_sports' => 'nullable|array',
            'preferred_sports.*' => 'integer|exists:sports,id',
            'preferred_position' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updateData = array_filter([
                'name' => $request->name,
                'phone' => $request->phone,
                'level' => $request->level,
                'preferred_sports' => $request->preferred_sports,
                'preferred_position' => $request->preferred_position,
            ], function ($value) {
                return $value !== null;
            });

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'level' => $user->level,
                        'preferred_sports' => $user->preferred_sports,
                        'preferred_position' => $user->preferred_position,
                        'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                        'roles' => $user->getRoleNames(),
                        'updated_at' => $user->updated_at,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            // Xóa avatar cũ nếu có
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Upload file mới
            $file = $request->file('avatar');
            $fileName = 'avatars/' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Lưu file vào storage/app/public
            $path = $file->storeAs('', $fileName, 'public');

            // Resize image nếu cần (nếu có package intervention/image)
            if (class_exists('Intervention\Image\Facades\Image')) {
                $fullPath = Storage::disk('public')->path($path);
                Image::make($fullPath)
                    ->fit(300, 300) // Resize to 300x300
                    ->save($fullPath, 90); // 90% quality
            }

            // Cập nhật database
            $user->update(['avatar' => $fileName]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar_url' => Storage::url($fileName),
                    'avatar_path' => $fileName,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->update(['avatar' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thay đổi email (với verification)
     */
    public function changeEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect',
            ], 400);
        }

        try {
            // Update email and reset email_verified_at
            $user->update([
                'email' => $request->new_email,
                'email_verified_at' => null,
            ]);

            // TODO: Send email verification
            // $user->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Email changed successfully. Please verify your new email.',
                'data' => [
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách devices đang đăng nhập
     */
    public function getDevices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $tokens = $user->tokens()
                ->select(['id', 'name', 'last_used_at', 'created_at'])
                ->orderBy('last_used_at', 'desc')
                ->get()
                ->map(function ($token) use ($request) {
                    return [
                        'id' => $token->id,
                        'device_name' => $token->name,
                        'last_used_at' => $token->last_used_at,
                        'created_at' => $token->created_at,
                        'is_current' => $request->user()->currentAccessToken()->id === $token->id,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'devices' => $tokens,
                    'total' => $tokens->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get devices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke một device token cụ thể
     */
    public function revokeDevice(Request $request, int $tokenId): JsonResponse
    {
        $validator = Validator::make(['token_id' => $tokenId], [
            'token_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token ID',
            ], 422);
        }

        try {
            $user = $request->user();
            $currentTokenId = $request->user()->currentAccessToken()->id;

            // Không cho phép revoke token hiện tại
            if ($tokenId == $currentTokenId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot revoke current session. Use logout instead.',
                ], 400);
            }

            $deleted = $user->tokens()->where('id', $tokenId)->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Device session revoked successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Device session not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke device session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
