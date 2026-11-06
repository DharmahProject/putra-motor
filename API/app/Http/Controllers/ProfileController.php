<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $directory = public_path('uploads/profile');
            File::ensureDirectoryExists($directory);
            $file = $request->file('logo');
            $filename = 'logo-' . now()->format('YmdHisv') . '.' . ($file->extension() ?: 'png');
            $file->move($directory, $filename);
            $data['logo_path'] = 'uploads/profile/' . $filename;
        }

        unset($data['logo']);
        $profile->fill($data)->save();

        return response()->json([
            'message' => 'Profil bengkel berhasil disimpan',
            'data' => $this->payload($profile->fresh()),
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
        $data['logo_url'] = $profile->logo_path ? url($profile->logo_path) : null;

        return $data;
    }
}
