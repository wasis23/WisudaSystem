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

        $query = SikeuPaymentCache::query()
            ->with(['wisudawan.programStudi'])
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

        $totalWisudawan = Wisudawan::count();
        $totalCached = SikeuPaymentCache::count();
        $totalLunas = SikeuPaymentCache::lunas()->count();
        $totalBelumLunas = SikeuPaymentCache::belumLunas()->count();
        $totalNominal = SikeuPaymentCache::lunas()->sum('total_bayar');
        $totalExtraGuests = SikeuPaymentCache::sum('jumlah_undangan_extra');
        $lastSync = SikeuPaymentCache::max('synced_at');

        $recentLogs = DB::table('external_sync_logs')
            ->where('source', 'sikeu')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/SikeuSync', [
            'stats' => [
                'total_wisudawan' => $totalWisudawan,
                'total_cached' => $totalCached,
                'total_lunas' => $totalLunas,
                'total_belum_lunas' => $totalBelumLunas,
                'total_nominal' => $totalNominal,
                'total_extra_guests' => $totalExtraGuests,
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
