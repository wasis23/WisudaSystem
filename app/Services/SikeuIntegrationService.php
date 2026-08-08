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
            // Query riwayat_bayar in SIKEU database
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
                    $totalExtraGuests += max(1, (int)($payment->jumlah_bayar / 150000));
                }
            }

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

        // Real default response if SIKEU is unreachable or no extra payment records found (Standard 2 guests)
        return [
            'has_paid_wisuda' => false,
            'tambahan_wisuda_paid_quota' => 0,
            'total_allowed_guests' => 2,
            'snack_quota' => 3,
        ];
    }
}
