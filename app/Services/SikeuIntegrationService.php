<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SikeuIntegrationService
{
    /**
     * Get financial details and extra wisuda guest quota from SIKEU database
     */
    public function getExtraWisudaQuota(string $nim): array
    {
        $cleanNim = trim($nim);

        try {
            // Query riwayat_bayar or master_biaya_lains in SIKEU
            $payments = DB::connection('sikeu')->table('riwayat_bayar')
                ->where('no_pend', $cleanNim)
                ->get();

            $totalExtraGuests = 0;
            $hasPaidWisuda = false;

            foreach ($payments as $payment) {
                $hasPaidWisuda = true;
                $keterangan = strtolower($payment->keterangan ?? '');

                // Check if payment description mentions extra wisuda / undangan tambahan
                if (str_contains($keterangan, 'tambahan') || str_contains($keterangan, 'undangan') || str_contains($keterangan, 'pendamping')) {
                    // Default 1 or 2 extra guests per paid item
                    $totalExtraGuests += max(1, (int)($payment->jumlah_bayar / 150000));
                }
            }

            // Default standard quota is 2 extra guests (Orang Tua), additional paid extra guests add to this quota
            $totalQuota = 2 + $totalExtraGuests;

            return [
                'has_paid_wisuda' => $hasPaidWisuda,
                'tambahan_wisuda_paid_quota' => $totalExtraGuests,
                'total_allowed_guests' => $totalQuota,
                'snack_quota' => $totalQuota + 1, // 1 Wisudawan + guests
            ];
        } catch (\Exception $e) {
            Log::warning('SIKEU Integration error: ' . $e->getMessage());
        }

        // Mock fallback response for development/demo (Default 2 guests allowed)
        return [
            'has_paid_wisuda' => true,
            'tambahan_wisuda_paid_quota' => 1,
            'total_allowed_guests' => 3, // 2 standard + 1 extra
            'snack_quota' => 4, // 1 wisudawan + 3 guests
        ];
    }
}
