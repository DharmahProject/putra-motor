<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Karyawan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => User::query()->with('karyawan:id,employee_id,name,email')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data = $this->withKaryawanIdentity($data);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'message' => 'User berhasil ditambahkan',
            'data' => $user,
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $this->validated($request, $user);
        $data = $this->withKaryawanIdentity($data);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User berhasil diperbarui',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus']);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'karyawan_id' => ['required', 'integer', 'exists:karyawan,id', Rule::unique('users', 'karyawan_id')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'role' => ['required', 'string', 'max:50'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::in(['Dashboard', 'Transaksi', 'Sparepart', 'Jasa', 'Pengaturan'])],
            'active' => ['required', 'boolean'],
        ]);
    }

    private function withKaryawanIdentity(array $data): array
    {
        $karyawan = Karyawan::query()->findOrFail($data['karyawan_id']);
        $data['name'] = $karyawan->name;
        $data['email'] = $karyawan->email;

        return $data;
    }
}
