<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class KaryawanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Karyawan::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $karyawan = DB::transaction(function () use ($data) {
            $numbers = Karyawan::query()
                ->lockForUpdate()
                ->where('employee_id', 'like', 'ID-PM-%')
                ->pluck('employee_id')
                ->map(fn (string $id) => (int) substr($id, 6));

            $data['employee_id'] = sprintf('ID-PM-%03d', ($numbers->max() ?? 0) + 1);

            return Karyawan::create($data);
        });

        return response()->json([
            'message' => 'Karyawan berhasil ditambahkan',
            'data' => $karyawan,
        ], 201);
    }

    public function update(Request $request, Karyawan $karyawan): JsonResponse
    {
        $data = $this->validated($request, $karyawan);
        $karyawan->update($data);

        return response()->json([
            'message' => 'Karyawan berhasil diperbarui',
            'data' => $karyawan->fresh(),
        ]);
    }

    public function destroy(Karyawan $karyawan): JsonResponse
    {
        $karyawan->delete();

        return response()->json(['message' => 'Karyawan berhasil dihapus']);
    }

    private function validated(Request $request, ?Karyawan $karyawan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', Rule::unique('karyawan', 'email')->ignore($karyawan?->id)],
            'shift' => ['required', Rule::in(['Pagi', 'Siang', 'Full Day'])],
            'active' => ['required', 'boolean'],
        ]);
    }
}
