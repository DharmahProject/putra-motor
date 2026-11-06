<?php

namespace App\Http\Controllers;

use App\Models\Masterpart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterSparepartController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Masterpart::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'stok' => ['required', 'integer', 'min:0'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $item = Masterpart::create($data);

        return response()->json([
            'message' => 'Sparepart berhasil disimpan',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, Masterpart $masterSparepart): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'stok' => ['required', 'integer', 'min:0'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $masterSparepart->update($data);

        return response()->json([
            'message' => 'Sparepart berhasil diperbarui',
            'data' => $masterSparepart,
        ]);
    }

    public function destroy(Masterpart $masterSparepart): JsonResponse
    {
        $masterSparepart->delete();

        return response()->json([
            'message' => 'Sparepart berhasil dihapus',
        ]);
    }
}
