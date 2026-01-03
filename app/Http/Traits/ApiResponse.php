<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 * 
 * Provides a standardized way to format API responses.
 */
trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        $data = null,
        string $message = 'Resource retrieved successfully',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message = 'Error occurred',
        int $statusCode = 400,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a not found response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404, []);
    }

    /**
     * Return an unauthorized response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401, []);
    }

    /**
     * Return a validation error response.
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed',
        int $statusCode = 422
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
