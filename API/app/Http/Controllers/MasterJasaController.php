<?php

namespace App\Http\Controllers;

use App\Models\Masterjasa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterJasaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Masterjasa::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $item = Masterjasa::create($data);

        return response()->json([
            'message' => 'Jasa berhasil disimpan',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, Masterjasa $masterJasa): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $masterJasa->update($data);

        return response()->json([
            'message' => 'Jasa berhasil diperbarui',
            'data' => $masterJasa,
        ]);
    }

    public function destroy(Masterjasa $masterJasa): JsonResponse
    {
        $masterJasa->delete();

        return response()->json([
            'message' => 'Jasa berhasil dihapus',
        ]);
    }
}
