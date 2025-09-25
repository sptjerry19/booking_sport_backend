<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponse
{
    /**
     * Return a success response
     */
    public static function success(
        $data = null,
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response
     */
    public static function error(
        string $message = 'Error occurred',
        int $code = Response::HTTP_BAD_REQUEST,
        $errors = null,
        $data = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a validation error response
     */
    public static function validationError(
        array $errors,
        string $message = 'Dữ liệu không hợp lệ',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY
    ): JsonResponse {
        return self::error($message, $code, $errors);
    }

    /**
     * Return a not found response
     */
    public static function notFound(
        string $message = 'Không tìm thấy dữ liệu',
        int $code = Response::HTTP_NOT_FOUND
    ): JsonResponse {
        return self::error($message, $code);
    }

    /**
     * Return an unauthorized response
     */
    public static function unauthorized(
        string $message = 'Không có quyền truy cập',
        int $code = Response::HTTP_UNAUTHORIZED
    ): JsonResponse {
        return self::error($message, $code);
    }

    /**
     * Return a forbidden response
     */
    public static function forbidden(
        string $message = 'Không có quyền thực hiện hành động này',
        int $code = Response::HTTP_FORBIDDEN
    ): JsonResponse {
        return self::error($message, $code);
    }

    /**
     * Return a server error response
     */
    public static function serverError(
        string $message = 'Lỗi máy chủ',
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR
    ): JsonResponse {
        return self::error($message, $code);
    }

    /**
     * Return a paginated response
     */
    public static function paginated(
        $paginatedData,
        string $message = 'Success',
        int $code = Response::HTTP_OK
    ): JsonResponse {
        $meta = [
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem(),
                'path' => $paginatedData->path(),
                'has_more_pages' => $paginatedData->hasMorePages(),
            ]
        ];

        return self::success(
            $paginatedData->items(),
            $message,
            $code,
            $meta
        );
    }

    /**
     * Return a created response
     */
    public static function created(
        $data = null,
        string $message = 'Tạo thành công',
        int $code = Response::HTTP_CREATED
    ): JsonResponse {
        return self::success($data, $message, $code);
    }

    /**
     * Return an updated response
     */
    public static function updated(
        $data = null,
        string $message = 'Cập nhật thành công',
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return self::success($data, $message, $code);
    }

    /**
     * Return a deleted response
     */
    public static function deleted(
        string $message = 'Xóa thành công',
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return self::success(null, $message, $code);
    }

    /**
     * Return a no content response
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
