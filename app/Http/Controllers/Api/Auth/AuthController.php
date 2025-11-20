<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Đăng ký tài khoản mới
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'nullable|string|max:20|unique:users',
            'level' => 'nullable|string|in:beginner,intermediate,advanced',
            'preferred_sports' => 'nullable|array',
            'preferred_sports.*' => 'integer|exists:sports,id',
            'preferred_position' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError(
                $validator->errors()->toArray(),
                __('auth.validation_failed')
            );
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'level' => $request->level ?? 'beginner',
                'preferred_sports' => $request->preferred_sports,
                'preferred_position' => $request->preferred_position,
            ]);

            // Assign default role
            $user->assignRole('user');

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            return ApiResponse::userResource(
                $user,
                $token,
                __('auth.registration_successful'),
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('auth.registration_failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Đăng nhập
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'remember' => 'nullable|boolean',
            'device_name' => 'nullable|string|max:255',
        ]);


        if ($validator->fails()) {
            return ApiResponse::validationError(
                $validator->errors()->toArray(),
                __('auth.validation_failed')
            );
        }

        // Kiểm tra thông tin đăng nhập
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return ApiResponse::error(__('auth.invalid_credentials'), Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $user = JWTAuth::user();

        return ApiResponse::userResource($user, $token, __('auth.login_successful'));
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::parseToken()->invalidate();

            return ApiResponse::success(null, __('auth.logout_successful'));
        } catch (JWTException $e) {
            return ApiResponse::error(
                __('auth.logout_failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Đăng xuất tất cả devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            JWTAuth::parseToken()->invalidate(true);

            return ApiResponse::success(null, __('auth.logout_all_successful'));
        } catch (JWTException $e) {
            return ApiResponse::error(
                __('auth.logout_failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::userResource($user);
    }

    /**
     * Quên mật khẩu - Gửi link reset
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError(
                $validator->errors()->toArray(),
                __('auth.validation_failed')
            );
        }

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return ApiResponse::success(null, __('auth.password_reset_link_sent'));
            }

            return ApiResponse::error(__('auth.password_reset_link_failed'), Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('auth.password_reset_link_failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Reset mật khẩu
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError(
                $validator->errors()->toArray(),
                __('auth.validation_failed')
            );
        }

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return ApiResponse::success(null, __('auth.password_reset_success'));
            }

            return ApiResponse::error(
                __('auth.password_reset_invalid'),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('auth.password_reset_failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Refresh token (tạo token mới)
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
            /** @var User $user */
            $user = JWTAuth::setToken($newToken)->toUser();

            return ApiResponse::userResource($user, $newToken, __('auth.token_refreshed'));
        } catch (JWTException $e) {
            return ApiResponse::error(
                __('auth.token_refresh_failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Thay đổi mật khẩu
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError(
                $validator->errors()->toArray(),
                __('auth.validation_failed')
            );
        }

        $user = $request->user();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error(__('auth.current_password_incorrect'), Response::HTTP_BAD_REQUEST);
        }

        try {
            // Cập nhật mật khẩu mới
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return ApiResponse::success(null, __('auth.password_change_success'));
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('auth.password_change_failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['error' => $e->getMessage()]
            );
        }
    }


    // Các phương thức quản trị viên tương tự có thể được thêm vào đây
    public function adminLogin(AdminLoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return ApiResponse::error(__('auth.invalid_credentials'), Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $user = JWTAuth::user();

        if (!$user->hasRole('admin')) {
            JWTAuth::setToken($token)->invalidate(true);

            return ApiResponse::error(__('auth.forbidden'), Response::HTTP_FORBIDDEN);
        }

        return ApiResponse::userResource($user, $token, __('auth.login_successful'));
    }
}
