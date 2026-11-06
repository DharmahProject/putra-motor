<?php

namespace App\Http\Controllers;

use App\Models\Masterpart;
use App\Models\Transaksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    private const STATUSES = ['Selesai', 'Dalam Pengerjaan'];

    private const PAYMENT_STATUSES = ['Lunas', 'Belum Bayar'];

    private const PAYMENTS = ['Tunai', 'Transfer', 'QRIS'];

    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
        ]);

        $bulan = (int) ($filters['bulan'] ?? now()->month);
        $tahun = (int) ($filters['tahun'] ?? now()->year);
        $stokMinimum = (int) ($filters['stok_minimum'] ?? 10);
        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $previousStart = $start->copy()->subMonth()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        $current = $this->periodSummary($start, $end);
        $previous = $this->periodSummary($previousStart, $previousEnd);
        $statuses = $this->statusCounts($start, $end);
        $payments = $this->paymentCounts($start, $end);
        $today = $this->todaySummary();

        return response()->json([
            'data' => [
                'periode' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'mulai' => $start->toDateString(),
                    'selesai' => $end->toDateString(),
                ],
                'pendapatan' => [
                    'total' => $current['pendapatan'],
                    'perubahan_persen' => $this->percentChange($current['pendapatan'], $previous['pendapatan']),
                ],
                'transaksi' => [
                    'total' => $current['jumlah'],
                    'perubahan_persen' => $this->percentChange($current['jumlah'], $previous['jumlah']),
                ],
                'servis_berlangsung' => [
                    'total' => Transaksi::query()->where('status', 'Dalam Pengerjaan')->count(),
                ],
                'rata_rata' => [
                    'total' => $current['rata_rata'],
                    'perubahan_persen' => $this->percentChange($current['rata_rata'], $previous['rata_rata']),
                ],
                'pendapatan_bulanan' => $this->yearlyRevenue($tahun),
                'status_pekerjaan' => $this->withShare(self::STATUSES, $statuses, 'status'),
                'status_pembayaran' => $this->withShare(self::PAYMENT_STATUSES, $this->paymentStatusCounts($start, $end), 'status'),
                'servis_hari_ini' => $today,
                'metode_pembayaran' => $this->withShare(self::PAYMENTS, $payments, 'metode'),
                'stok_menipis' => $this->lowStock($stokMinimum),
            ],
        ]);
    }

    private function periodSummary(Carbon $start, Carbon $end): array
    {
        $rows = Transaksi::query()
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get(['status', 'status_pembayaran', 'total']);

        $jumlah = $rows->count();
        $pendapatan = (int) $rows->where('status_pembayaran', 'Lunas')->sum('total');
        $total = (int) $rows->sum('total');

        return [
            'jumlah' => $jumlah,
            'pendapatan' => $pendapatan,
            'rata_rata' => $jumlah > 0 ? (int) round($total / $jumlah) : 0,
        ];
    }

    private function statusCounts(Carbon $start, Carbon $end)
    {
        return Transaksi::query()
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');
    }

    private function paymentStatusCounts(Carbon $start, Carbon $end)
    {
        return Transaksi::query()
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('status_pembayaran, COUNT(*) as jumlah')
            ->groupBy('status_pembayaran')
            ->pluck('jumlah', 'status_pembayaran');
    }

    private function paymentCounts(Carbon $start, Carbon $end)
    {
        return Transaksi::query()
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('metode_pembayaran, COUNT(*) as jumlah')
            ->groupBy('metode_pembayaran')
            ->pluck('jumlah', 'metode_pembayaran');
    }

    private function todaySummary(): array
    {
        $counts = Transaksi::query()
            ->whereDate('tanggal', now()->toDateString())
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $selesai = (int) ($counts['Selesai'] ?? 0);
        $menunggu = (int) ($counts['Dalam Pengerjaan'] ?? 0);
        $total = $selesai + $menunggu;

        return [
            'tanggal' => now()->toDateString(),
            'total' => $total,
            'selesai' => $selesai,
            'menunggu' => $menunggu,
            'persen_selesai' => $total > 0 ? round($selesai / $total * 100, 1) : 0,
        ];
    }

    private function yearlyRevenue(int $tahun): array
    {
        $rows = Transaksi::query()
            ->whereYear('tanggal', $tahun)
            ->where('status_pembayaran', 'Lunas')
            ->selectRaw('MONTH(tanggal) as bulan, SUM(total) as total')
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total', 'bulan');
        $previous = (int) Transaksi::query()
            ->whereYear('tanggal', $tahun - 1)
            ->where('status_pembayaran', 'Lunas')
            ->sum('total');
        $total = (int) $rows->sum();

        $bulan = [];
        foreach (self::MONTHS as $index => $label) {
            $bulan[] = [
                'bulan' => $index + 1,
                'label' => $label,
                'total' => (int) ($rows[$index + 1] ?? 0),
            ];
        }

        return [
            'tahun' => $tahun,
            'total' => $total,
            'perubahan_persen' => $this->percentChange($total, $previous),
            'bulan' => $bulan,
        ];
    }

    private function lowStock(int $minimum): array
    {
        return Masterpart::query()
            ->where('stok', '<=', $minimum)
            ->orderBy('stok')
            ->orderBy('nama')
            ->limit(10)
            ->get(['id', 'nama', 'stok'])
            ->map(fn (Masterpart $part) => [
                'id' => $part->id,
                'nama' => $part->nama,
                'stok' => $part->stok,
                'stok_minimum' => $minimum,
            ])
            ->values()
            ->all();
    }

    private function withShare(array $keys, $counts, string $field): array
    {
        $jumlah = [];
        $total = 0;
        foreach ($keys as $key) {
            $jumlah[$key] = (int) ($counts[$key] ?? 0);
            $total += $jumlah[$key];
        }

        return array_map(fn (string $key) => [
            $field => $key,
            'jumlah' => $jumlah[$key],
            'persen' => $total > 0 ? round($jumlah[$key] / $total * 100, 1) : 0,
        ], $keys);
    }

    private function percentChange(int|float $current, int|float $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
