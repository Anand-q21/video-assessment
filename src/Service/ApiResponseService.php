<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponseService
{
    public function success($data = null, string $message = 'Success', array $pagination = null): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'message' => $message
        ];

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return new JsonResponse($response);
    }

    public function error(string $message = 'Error', $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return new JsonResponse($response, $statusCode);
    }

    public function paginate(array $data, int $page, int $limit, int $total): array
    {
        return [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit),
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ];
    }
}