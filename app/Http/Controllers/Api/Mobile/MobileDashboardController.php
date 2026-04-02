<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\Attendance\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Attendance\MobileDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MobileDashboardController extends Controller
{
    public function __construct(
        private readonly MobileDashboardService $mobileDashboardService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return $this->errorResponse(
                message: 'Unauthorized.',
                errorCode: 'UNAUTHORIZED',
                statusCode: 401,
            );
        }

        try {
            $dashboard = $this->mobileDashboardService->buildForUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Mobile dashboard retrieved successfully.',
                'data' => $dashboard->toArray(),
            ]);
        } catch (AttendanceException $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                errorCode: $exception->getErrorCode(),
                statusCode: $exception->getStatusCode(),
                context: $this->safeContext($exception->getContext()),
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                message: 'Terjadi kesalahan pada server.',
                errorCode: 'INTERNAL_SERVER_ERROR',
                statusCode: 500,
            );
        }
    }

    private function errorResponse(
        string $message,
        string $errorCode,
        int $statusCode,
        array $context = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'status_code' => $statusCode,
            'context' => $context,
        ], $statusCode);
    }

    private function safeContext(array $context): array
    {
        $sensitiveFragments = [
            'token',
            'auth',
            'authorization',
            'password',
            'secret',
            'credential',
            'bearer',
            'qr',
        ];

        $sanitized = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            foreach ($sensitiveFragments as $fragment) {
                if (str_contains($normalizedKey, $fragment)) {
                    $sanitized[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
