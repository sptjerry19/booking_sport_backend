<?php

namespace App\Http\Responses;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponse
{
    /**
     * Return a success response
     */
    public static function success(
        $data = null,
        string $message = null,
        int $code = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $message ??= __('messages.success');

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
     * Return a user resource response
     */
    public static function userResource(
        $user,
        ?string $token = null,
        string $message = null,
        int $code = Response::HTTP_OK,
        array $additional = []
    ): JsonResponse {
        $data = array_merge([
            'user' => new UserResource($user),
        ], $additional);

        if (!empty($token)) {
            $data['token'] = $token;
            $data['token_type'] = 'Bearer';
        }

        return self::success($data, $message ?? __('messages.success'), $code);
    }

    /**
     * Return an error response
     */
    public static function error(
        string $message = null,
        int $code = Response::HTTP_BAD_REQUEST,
        $errors = null,
        $data = null
    ): JsonResponse {
        $message ??= __('messages.error');

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
        string $message = null,
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY
    ): JsonResponse {
        return self::error($message ?? __('messages.validation_failed'), $code, $errors);
    }

    /**
     * Return a not found response
     */
    public static function notFound(
        string $message = null,
        int $code = Response::HTTP_NOT_FOUND
    ): JsonResponse {
        return self::error($message ?? __('messages.not_found'), $code);
    }

    /**
     * Return an unauthorized response
     */
    public static function unauthorized(
        string $message = null,
        int $code = Response::HTTP_UNAUTHORIZED
    ): JsonResponse {
        return self::error($message ?? __('messages.unauthorized'), $code);
    }

    /**
     * Return a forbidden response
     */
    public static function forbidden(
        string $message = null,
        int $code = Response::HTTP_FORBIDDEN
    ): JsonResponse {
        return self::error($message ?? __('messages.forbidden'), $code);
    }

    /**
     * Return a server error response
     */
    public static function serverError(
        string $message = null,
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR
    ): JsonResponse {
        return self::error($message ?? __('messages.server_error'), $code);
    }

    /**
     * Return a paginated response
     */
    public static function paginated(
        $paginatedData,
        string $message = null,
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
            $message ?? __('messages.success'),
            $code,
            $meta
        );
    }

    /**
     * Return a created response
     */
    public static function created(
        $data = null,
        string $message = null,
        int $code = Response::HTTP_CREATED
    ): JsonResponse {
        return self::success($data, $message ?? __('messages.created'), $code);
    }

    /**
     * Return an updated response
     */
    public static function updated(
        $data = null,
        string $message = null,
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return self::success($data, $message ?? __('messages.updated'), $code);
    }

    /**
     * Return a deleted response
     */
    public static function deleted(
        string $message = null,
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return self::success(null, $message ?? __('messages.deleted'), $code);
    }

    /**
     * Return a no content response
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
