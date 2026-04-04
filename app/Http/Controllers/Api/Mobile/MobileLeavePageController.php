<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Leave\MobileLeavePageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileLeavePageController extends Controller
{
    public function __construct(
        private readonly MobileLeavePageService $mobileLeavePageService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        abort_if($user === null, 401, 'Unauthenticated.');

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $result = $this->mobileLeavePageService->buildForUser($user, $page, $perPage);

        return response()->json([
            'message' => 'Data halaman leave berhasil diambil.',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }
}

