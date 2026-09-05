<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SikeuPaymentCache;
use App\Models\Wisudawan;
use App\Services\SikeuIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SikeuSyncController extends Controller
{
    protected SikeuIntegrationService $sikeuService;

    public function __construct(SikeuIntegrationService $sikeuService)
    {
        $this->sikeuService = $sikeuService;
    }

    /**
     * Halaman status sync SIKEU & Manajemen Pembayaran Wisuda
     */
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));
        $status = $request->input('status', '');

        $activePeriode = \App\Models\PeriodeWisuda::getActive() ?? \App\Models\PeriodeWisuda::latest()->first();
        $periodeId = $activePeriode?->id;

        $query = SikeuPaymentCache::query()
            ->with(['wisudawan.programStudi'])
            ->whereHas('wisudawan', function ($w) use ($periodeId) {
                if ($periodeId) {
                    $w->where('periode_wisuda_id', $periodeId);
                }
            })
            ->orderBy('status_bayar', 'asc') // belum_lunas first
            ->orderBy('nama', 'asc');

        if (!empty($q)) {
            $query->search($q);
        }

        if ($status === 'lunas') {
            $query->lunas();
        } elseif ($status === 'belum_lunas') {
            $query->belumLunas();
        }

        // Base query wisudawan pada periode ini
        $baseStatsQuery = SikeuPaymentCache::query()
            ->whereHas('wisudawan', function ($w) use ($periodeId) {
                if ($periodeId) {
                    $w->where('periode_wisuda_id', $periodeId);
                }
            });

        $totalWisudawan = Wisudawan::when($periodeId, fn($w) => $w->where('periode_wisuda_id', $periodeId))->count();
        $totalCached = (clone $baseStatsQuery)->count();
        $totalLunas = (clone $baseStatsQuery)->lunas()->count();
        $totalBelumLunas = (clone $baseStatsQuery)->belumLunas()->count();
        $totalNominal = (clone $baseStatsQuery)->lunas()->sum('total_bayar');
        $totalExtraGuests = (clone $baseStatsQuery)->lunas()->sum('jumlah_undangan_extra');
        $lastSync = (clone $baseStatsQuery)->max('synced_at') ?? SikeuPaymentCache::max('synced_at');

        $recentLogs = DB::table('external_sync_logs')
            ->where('source', 'sikeu')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $maxExtraGuests = $activePeriode?->max_tamu_tambahan ?? 60;

        return Inertia::render('Admin/SikeuSync', [
            'stats' => [
                'total_wisudawan' => $totalWisudawan,
                'total_cached' => $totalCached,
                'total_lunas' => $totalLunas,
                'total_belum_lunas' => $totalBelumLunas,
                'total_nominal' => $totalNominal,
                'total_extra_guests' => $totalExtraGuests,
                'max_extra_guests' => $maxExtraGuests,
                'last_sync' => $lastSync,
            ],
            'recentLogs' => $recentLogs,
            'payments' => $query->paginate(50)->withQueryString(),
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Trigger sync pembayaran dari SIKEU
     */
    public function sync(Request $request)
    {
        $logData = [
            'source' => 'sikeu',
            'action' => 'sync_payments',
            'records_fetched' => 0,
            'records_inserted' => 0,
            'records_updated' => 0,
            'status' => 'failed',
            'notes' => null,
            'filter_params' => null,
            'triggered_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            $result = $this->sikeuService->syncAll();

            $logData['records_fetched'] = $result['records_fetched'];
            $logData['records_inserted'] = $result['records_inserted'];
            $logData['records_updated'] = $result['records_updated'];
            $logData['status'] = 'success';
            $logData['notes'] = "Berhasil sync {$result['records_fetched']} data pembayaran wisuda dari SIKEU.";

            DB::table('external_sync_logs')->insert($logData);

            return back()->with('success', "✅ Sync SIKEU berhasil! {$result['records_fetched']} data pembayaran diproses ({$result['records_inserted']} baru, {$result['records_updated']} diperbarui).");
        } catch (\Exception $e) {
            Log::error('SikeuSync error: ' . $e->getMessage());
            $logData['notes'] = $e->getMessage();
            DB::table('external_sync_logs')->insert($logData);

            return back()->with('error', '❌ Sync SIKEU gagal: ' . $e->getMessage());
        }
    }

    /**
     * Manual Toggle/Update Status Pembayaran Mahasiswa oleh Admin
     */
    public function toggle(Request $request, $id)
    {
        $payment = SikeuPaymentCache::findOrFail($id);

        $newStatus = $payment->status_bayar === 'lunas' ? 'belum_lunas' : 'lunas';
        $extraGuests = $request->input('extra_guests', $payment->jumlah_undangan_extra);
        $nominal = $request->input('nominal', null);

        $this->sikeuService->setPaymentStatus($payment->nim, $newStatus, (int)$extraGuests, $nominal);

        return back()->with('success', "Status pembayaran untuk {$payment->nama} (NIM: {$payment->nim}) berhasil diubah menjadi " . strtoupper($newStatus) . "!");
    }

    /**
     * Update detail kuota ekstra undangan & nominal
     */
    public function updateDetail(Request $request, $id)
    {
        $payment = SikeuPaymentCache::findOrFail($id);

        $request->validate([
            'status_bayar' => 'required|in:lunas,belum_lunas',
            'jumlah_undangan_extra' => 'required|integer|min:0|max:10',
            'total_bayar' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $this->sikeuService->setPaymentStatus(
            $payment->nim,
            $request->status_bayar,
            (int)$request->jumlah_undangan_extra,
            $request->total_bayar ? (int)$request->total_bayar : null,
            $request->keterangan
        );

        return back()->with('success', "Data pembayaran {$payment->nama} berhasil diperbarui!");
    }
}
