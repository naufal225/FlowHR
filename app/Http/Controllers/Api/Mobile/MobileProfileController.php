<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ProfilePasswordUpdateRequest;
use App\Http\Requests\Mobile\ProfileUpdateRequest;
use App\Support\MobileUserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileProfileController extends Controller
{
    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401, 'Unauthenticated.');

        if ($request->hasFile('profile_photo')) {
            $existingPath = $this->resolveProfileStoragePath($user->url_profile);
            if ($existingPath !== null) {
                Storage::disk('public')->delete($existingPath);
            }

            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->url_profile = '/storage/' . $path;
        }

        $user->name = (string) $request->input('name');
        $user->email = (string) $request->input('email');
        $user->save();

        $user->loadMissing(['roles:id,name', 'division:id,name', 'officeLocation:id,name,address']);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => MobileUserTransformer::transform($user),
        ]);
    }

    public function updatePassword(ProfilePasswordUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401, 'Unauthenticated.');

        $currentPassword = (string) $request->input('current_password');
        if (! Hash::check($currentPassword, (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini tidak cocok.',
                'errors' => [
                    'current_password' => ['Password saat ini tidak cocok.'],
                ],
            ], 422);
        }

        $user->password = Hash::make((string) $request->input('new_password'));
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
            'data' => null,
        ]);
    }

    private function resolveProfileStoragePath(?string $urlProfile): ?string
    {
        if ($urlProfile === null || trim($urlProfile) === '') {
            return null;
        }

        $path = parse_url($urlProfile, PHP_URL_PATH) ?? $urlProfile;
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (Str::startsWith($path, '/storage/')) {
            return ltrim(Str::after($path, '/storage/'), '/');
        }

        return null;
    }
}
