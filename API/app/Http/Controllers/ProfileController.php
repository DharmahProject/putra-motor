<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => $this->payload(Profile::query()->first()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address' => ['nullable', 'string', 'max:1000'],
            'bank_1_name' => ['nullable', 'string', 'max:100'],
            'bank_1_account_name' => ['nullable', 'string', 'max:150'],
            'bank_1_account' => ['nullable', 'string', 'max:50'],
            'bank_2_name' => ['nullable', 'string', 'max:100'],
            'bank_2_account_name' => ['nullable', 'string', 'max:150'],
            'bank_2_account' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $profile = Profile::query()->firstOrNew(['id' => 1]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            if (env('VERCEL') || env('PROFILE_LOGO_DRIVER') === 'database') {
                $contents = file_get_contents($file->getRealPath());
                if ($contents === false) {
                    return response()->json(['message' => 'Logo gagal dibaca'], 422);
                }
                $data['logo_data'] = $contents;
                $data['logo_mime'] = $file->getMimeType() ?: 'image/png';
                $data['logo_path'] = null;
            } else {
                $directory = public_path('uploads/profile');
                File::ensureDirectoryExists($directory);
                $filename = 'logo-' . now()->format('YmdHisv') . '.' . ($file->extension() ?: 'png');
                $file->move($directory, $filename);
                $data['logo_path'] = 'uploads/profile/' . $filename;
                $data['logo_data'] = null;
                $data['logo_mime'] = null;
            }
        }

        unset($data['logo']);
        $profile->fill($data)->save();

        return response()->json([
            'message' => 'Profil bengkel berhasil disimpan',
            'data' => $this->payload($profile->fresh()),
        ]);
    }

    public function logo(): Response
    {
        $profile = Profile::query()->first();
        if (!$profile || !$profile->logo_data) {
            abort(404);
        }

        return response($profile->logo_data, 200, [
            'Content-Type' => $profile->logo_mime ?: 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function payload(?Profile $profile): array
    {
        if (!$profile) {
            return [
                'address' => '',
                'bank_1_name' => '',
                'bank_1_account_name' => '',
                'bank_1_account' => '',
                'bank_2_name' => '',
                'bank_2_account_name' => '',
                'bank_2_account' => '',
                'logo_path' => null,
                'logo_url' => null,
            ];
        }

        $data = $profile->toArray();
        $data['logo_url'] = $profile->logo_data
            ? url('/API/public/api/profile/logo') . '?v=' . rawurlencode((string) $profile->updated_at?->timestamp)
            : ($profile->logo_path ? url($profile->logo_path) : null);

        return $data;
    }
}
