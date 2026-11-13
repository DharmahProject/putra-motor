<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Transaksi;
use App\Support\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TransaksiController extends Controller
{
    private const STATUSES = ['Dalam Pengerjaan', 'Selesai'];

    private const PAYMENT_STATUSES = ['Belum Bayar', 'Lunas'];

    private const PAYMENTS = ['Tunai', 'Transfer', 'QRIS'];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'status_pembayaran' => ['nullable', Rule::in(self::PAYMENT_STATUSES)],
            'metode_pembayaran' => ['nullable', Rule::in(self::PAYMENTS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(['nomor', 'tanggal', 'total'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        if (! empty($filters['dari']) && ! empty($filters['sampai']) && $filters['dari'] > $filters['sampai']) {
            [$filters['dari'], $filters['sampai']] = [$filters['sampai'], $filters['dari']];
        }

        $perPage = (int) ($filters['per_page'] ?? 10);
        $query = Transaksi::query();
        $this->applyDateRange($query, $filters);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('nomor', 'like', "%{$search}%")
                    ->orWhere('pelanggan', 'like', "%{$search}%")
                    ->orWhere('plat', 'like', "%{$search}%")
                    ->orWhere('kendaraan', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['status_pembayaran'])) {
            $query->where('status_pembayaran', $filters['status_pembayaran']);
        }

        if (! empty($filters['metode_pembayaran'])) {
            $query->where('metode_pembayaran', $filters['metode_pembayaran']);
        }

        $sortColumns = [
            'nomor' => 'nomor',
            'tanggal' => 'tanggal',
            'total' => 'total',
        ];
        $sort = $sortColumns[$filters['sort'] ?? ''] ?? null;
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        if ($sort) {
            $query->orderBy($sort, $direction)->orderByDesc('nomor');
        } else {
            $query->orderByDesc('tanggal')->orderByDesc('nomor');
        }

        $page = $query->paginate($perPage)->withQueryString();
        $counts = $this->applyDateRange(Transaksi::query(), $filters)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return response()->json([
            'data' => $page->getCollection()->map(fn (Transaksi $transaksi) => $this->listPayload($transaksi))->values(),
            'pagination' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
            'summary' => [
                'total' => (int) $counts->sum(),
                'dalam_pengerjaan' => (int) ($counts['Dalam Pengerjaan'] ?? 0),
                'selesai' => (int) ($counts['Selesai'] ?? 0),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validated($request);

        $transaksi = DB::transaction(function () use ($payload) {
            $transaksi = Transaksi::create([
                'nomor' => $this->nextNomor(),
                ...$payload['header'],
            ]);
            $this->syncItems($transaksi, $payload['items']);

            return $transaksi->load('items');
        });

        return response()->json([
            'message' => 'Transaksi berhasil disimpan',
            'data' => $this->detailPayload($transaksi),
        ], 201);
    }

    public function show(string $transaksi): JsonResponse
    {
        return response()->json([
            'data' => $this->detailPayload($this->find($transaksi)),
        ]);
    }

    public function invoice(string $transaksi): Response
    {
        $record = $this->find($transaksi);
        $statusClass = [
            'Selesai' => 'lunas',
            'Dalam Pengerjaan' => 'proses',
        ][$record->status] ?? 'proses';

        $pdf = Pdf::loadView('invoices.kwitansi', [
            'logo' => $this->logoDataUri(),
            'nomor' => $record->nomor,
            'tanggal' => $record->tanggal->locale('id')->translatedFormat('d M Y'),
            'status_label' => mb_strtoupper($record->status),
            'status_class' => $statusClass,
            'pelanggan' => $record->pelanggan,
            'telepon' => $record->telepon,
            'kendaraan' => $record->kendaraan,
            'plat' => $record->plat,
            'odometer' => $record->odometer !== null ? number_format($record->odometer, 0, ',', '.').' km' : '-',
            'advisor' => $record->advisor ?: '-',
            'metode' => $record->metode_pembayaran,
            'diskon' => number_format($record->diskon, 0, ',', '.'),
            'total' => number_format($record->total, 0, ',', '.'),
            'terbilang' => Terbilang::rupiah((int) $record->total),
            'groups' => $this->invoiceGroups($record),
            'banks' => $this->invoiceBanks(),
            'qris' => $this->qrisDataUri(),
        ])->setPaper('a4');

        return $pdf->download('kwitansi-'.$record->nomor.'.pdf');
    }

    public function update(Request $request, string $transaksi): JsonResponse
    {
        $record = $this->find($transaksi);
        $payload = $this->validated($request);

        $record = DB::transaction(function () use ($record, $payload) {
            $record->update($payload['header']);
            $record->items()->delete();
            $this->syncItems($record, $payload['items']);

            return $record->load('items');
        });

        return response()->json([
            'message' => 'Transaksi berhasil diperbarui',
            'data' => $this->detailPayload($record),
        ]);
    }

    public function updatePaymentStatus(Request $request, string $transaksi): JsonResponse
    {
        $data = $request->validate([
            'status_pembayaran' => ['required', Rule::in(self::PAYMENT_STATUSES)],
            'metode_pembayaran' => ['required', Rule::in(self::PAYMENTS)],
        ]);
        $record = $this->find($transaksi);
        $record->update($data);

        return response()->json([
            'message' => 'Status pembayaran berhasil diperbarui',
            'data' => $this->listPayload($record->fresh()),
        ]);
    }

    public function updateWorkStatus(Request $request, string $transaksi): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);
        $record = $this->find($transaksi);
        $record->update($data);

        return response()->json([
            'message' => 'Status pengerjaan berhasil diperbarui',
            'data' => $this->listPayload($record->fresh()),
        ]);
    }

    private function validated(Request $request): array
    {
        $this->normalize($request);

        $data = $request->validate([
            'tanggal' => ['nullable', 'date'],
            'pelanggan' => ['required', 'string', 'max:150'],
            'telepon' => ['required', 'string', 'max:30'],
            'kendaraan' => ['required', 'string', 'max:150'],
            'plat' => ['required', 'string', 'max:20'],
            'advisor' => ['nullable', 'string', 'max:150'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'jenis_servis' => ['nullable', 'string', 'max:150'],
            'diskon' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'status_pembayaran' => ['nullable', Rule::in(self::PAYMENT_STATUSES)],
            'metode_pembayaran' => ['nullable', Rule::in(self::PAYMENTS)],
            'items' => ['nullable', 'array'],
            'items.*.jenis' => ['required', Rule::in(['sparepart', 'jasa', 'lainnya'])],
            'items.*.masterpart_id' => ['nullable', 'integer', 'exists:masterpart,id'],
            'items.*.masterjasa_id' => ['nullable', 'integer', 'exists:masterjasa,id'],
            'items.*.nama' => ['required', 'string', 'max:150'],
            'items.*.keterangan' => ['nullable', 'string', 'max:255'],
            'items.*.satuan' => ['nullable', 'string', 'max:30'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga' => ['required', 'integer', 'min:0'],
        ]);

        $items = array_map(function (array $item) {
            if (($item['jenis'] ?? '') !== 'sparepart') {
                $item['masterpart_id'] = null;
            } else {
                $item['satuan'] = $item['satuan'] ?? 'Pcs';
            }

            if (($item['jenis'] ?? '') !== 'jasa') {
                $item['masterjasa_id'] = null;
            }

            return $item;
        }, $data['items'] ?? []);

        $diskon = (int) ($data['diskon'] ?? 0);
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['qty'] * $item['harga'];
        }

        if ($diskon > $subtotal) {
            throw ValidationException::withMessages([
                'diskon' => 'Diskon tidak boleh lebih besar dari subtotal.',
            ]);
        }

        return [
            'header' => [
                'tanggal' => $data['tanggal'] ?? now()->toDateString(),
                'pelanggan' => $data['pelanggan'],
                'telepon' => $data['telepon'],
                'kendaraan' => $data['kendaraan'],
                'plat' => $data['plat'],
                'advisor' => $data['advisor'] ?? null,
                'odometer' => $data['odometer'] ?? null,
                'jenis_servis' => $this->jenisServis($items, $data['jenis_servis'] ?? null),
                'diskon' => $diskon,
                'total' => $subtotal - $diskon,
                'status' => $data['status'] ?? 'Dalam Pengerjaan',
                'status_pembayaran' => $data['status_pembayaran'] ?? 'Belum Bayar',
                'metode_pembayaran' => $data['metode_pembayaran'] ?? 'Tunai',
            ],
            'items' => $items,
        ];
    }

    private function normalize(Request $request): void
    {
        $merged = [];

        if ($request->exists('plat')) {
            $merged['plat'] = mb_strtoupper(trim((string) $request->input('plat')));
        }

        if ($request->exists('odometer')) {
            $merged['odometer'] = $this->digitsOrNull($request->input('odometer'));
        }

        if ($request->exists('diskon')) {
            $merged['diskon'] = $this->digitsOrNull($request->input('diskon')) ?? 0;
        }

        $items = $request->input('items');
        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach (['qty', 'harga', 'masterpart_id', 'masterjasa_id'] as $field) {
                    if (array_key_exists($field, $item)) {
                        $item[$field] = $this->digitsOrNull($item[$field]);
                    }
                }
                $items[$index] = $item;
            }
            $merged['items'] = $items;
        }

        if ($merged) {
            $request->merge($merged);
        }
    }

    private function digitsOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? null : (int) $digits;
    }

    private function jenisServis(array $items, ?string $given): string
    {
        $given = trim((string) $given);
        if ($given !== '') {
            return $given;
        }

        $names = [];
        foreach ($items as $item) {
            $name = trim((string) ($item['nama'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return mb_substr($names ? implode(' + ', $names) : '-', 0, 150);
    }

    private function syncItems(Transaksi $transaksi, array $items): void
    {
        foreach ($items as $index => $item) {
            $transaksi->items()->create([
                'jenis' => $item['jenis'],
                'masterpart_id' => $item['masterpart_id'] ?? null,
                'masterjasa_id' => $item['masterjasa_id'] ?? null,
                'nama' => $item['nama'],
                'keterangan' => $item['keterangan'] ?? null,
                'satuan' => $item['satuan'] ?? null,
                'qty' => $item['qty'],
                'harga' => $item['harga'],
                'urutan' => $index + 1,
            ]);
        }
    }

    private function nextNomor(): string
    {
        $prefix = 'TRX-'.now()->year.'-';
        $last = Transaksi::query()
            ->where('nomor', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('nomor')
            ->value('nomor');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function applyDateRange($query, array $filters)
    {
        if (! empty($filters['dari'])) {
            $query->whereDate('tanggal', '>=', $filters['dari']);
        }
        if (! empty($filters['sampai'])) {
            $query->whereDate('tanggal', '<=', $filters['sampai']);
        }

        return $query;
    }

    private function invoiceBanks(): array
    {
        $profile = Profile::query()->first();
        $names = array_values(array_filter([
            $profile?->bank_1_account_name,
            $profile?->bank_2_account_name,
        ]));
        $rekening = [];
        foreach ([
            [$profile?->bank_1_account, $profile?->bank_1_name],
            [$profile?->bank_2_account, $profile?->bank_2_name],
        ] as [$account, $bank]) {
            if (! $account) {
                continue;
            }
            $rekening[] = $bank ? $account.' ('.mb_strtoupper($bank).')' : $account;
        }

        return [
            'atas_nama' => $names ? implode(' / ', array_unique($names)) : '-',
            'rekening' => $rekening,
        ];
    }

    private function qrisDataUri(): ?string
    {
        $path = base_path('../qris.jpeg');
        if (! is_file($path)) {
            return null;
        }

        // File asli dipakai apa adanya supaya pola QR tidak rusak oleh kompresi ulang.
        return 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($path));
    }

    private function logoDataUri(): ?string
    {
        $path = base_path('../logo.png');
        if (! is_file($path)) {
            return null;
        }

        if (! function_exists('imagecreatefrompng') || ! function_exists('imagejpeg')) {
            return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
        }

        $source = imagecreatefrompng($path);
        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = 480;
        $targetHeight = (int) round($height * ($targetWidth / max(1, $width)));
        // Keep the alpha channel: JPEG has no transparency and turns the transparent background black.
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        ob_start();
        imagepng($canvas, null, 9);
        $binary = ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        return $binary ? 'data:image/png;base64,'.base64_encode($binary) : null;
    }

    private function invoiceGroups(Transaksi $transaksi): array
    {
        $definitions = [
            'sparepart' => ['label' => 'SPAREPART', 'subtotal_label' => 'Subtotal Sparepart'],
            'jasa' => ['label' => 'JASA SERVIS', 'subtotal_label' => 'Subtotal Jasa'],
            'lainnya' => ['label' => 'BIAYA LAINNYA', 'subtotal_label' => 'Biaya Lainnya'],
        ];
        $groups = [];
        foreach ($definitions as $jenis => $definition) {
            $groups[$jenis] = $definition + ['items' => [], 'subtotal_value' => 0];
        }

        foreach ($transaksi->items as $item) {
            $jenis = isset($groups[$item->jenis]) ? $item->jenis : 'lainnya';
            $jumlah = $item->qty * $item->harga;
            $groups[$jenis]['items'][] = [
                'nama' => $item->nama,
                'keterangan' => $item->keterangan,
                'qty' => $item->qty,
                'harga' => number_format($item->harga, 0, ',', '.'),
                'jumlah' => number_format($jumlah, 0, ',', '.'),
            ];
            $groups[$jenis]['subtotal_value'] += $jumlah;
        }

        return array_map(function (array $group) {
            $group['subtotal'] = number_format($group['subtotal_value'], 0, ',', '.');
            unset($group['subtotal_value']);

            return $group;
        }, array_values($groups));
    }

    private function find(string $key): Transaksi
    {
        $query = Transaksi::query()->with('items');
        $record = ctype_digit($key)
            ? $query->find($key)
            : $query->where('nomor', $key)->first();

        if (! $record) {
            abort(response()->json(['message' => 'Transaksi tidak ditemukan'], 404));
        }

        return $record;
    }

    private function listPayload(Transaksi $transaksi): array
    {
        return [
            'id' => $transaksi->id,
            'nomor' => $transaksi->nomor,
            'tanggal' => $transaksi->tanggal->format('Y-m-d'),
            'pelanggan' => $transaksi->pelanggan,
            'telepon' => $transaksi->telepon,
            'kendaraan' => $transaksi->kendaraan,
            'plat' => $transaksi->plat,
            'jenis_servis' => $transaksi->jenis_servis,
            'total' => $transaksi->total,
            'status' => $transaksi->status,
            'status_pembayaran' => $transaksi->status_pembayaran,
            'metode_pembayaran' => $transaksi->metode_pembayaran,
        ];
    }

    private function detailPayload(Transaksi $transaksi): array
    {
        $transaksi->loadMissing('items');

        return [
            ...$this->listPayload($transaksi),
            'advisor' => $transaksi->advisor,
            'odometer' => $transaksi->odometer,
            'diskon' => $transaksi->diskon,
            'subtotal' => $transaksi->total + $transaksi->diskon,
            'items' => $transaksi->items->map(fn ($item) => [
                'id' => $item->id,
                'jenis' => $item->jenis,
                'masterpart_id' => $item->masterpart_id,
                'masterjasa_id' => $item->masterjasa_id,
                'nama' => $item->nama,
                'keterangan' => $item->keterangan,
                'satuan' => $item->satuan,
                'qty' => $item->qty,
                'harga' => $item->harga,
                'total' => $item->qty * $item->harga,
            ])->values(),
        ];
    }
}
