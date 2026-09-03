<?php

namespace App\Http\Controllers\Wisudawan;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\Wisudawan;
use App\Services\QrCodeService;
use App\Services\SikeuIntegrationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TicketPdfController extends Controller
{
    protected SikeuIntegrationService $sikeuService;

    public function __construct(SikeuIntegrationService $sikeuService)
    {
        $this->sikeuService = $sikeuService;
    }

    public function export(Request $request, $wisudawanId = null)
    {
        $user = auth()->user();
        
        if ($wisudawanId && in_array($user?->role, ['admin_utama', 'admin', 'panitia', 'superadmin'])) {
            $wisudawan = Wisudawan::with(['programStudi', 'periodeWisuda', 'tamuTambahan'])->findOrFail($wisudawanId);
        } else {
            $wisudawan = $user?->wisudawan ? $user->wisudawan->load(['programStudi', 'periodeWisuda', 'tamuTambahan']) : null;
        }

        if (!$wisudawan) {
            return redirect()->route('wisudawan.dashboard')->with('error', 'Data wisudawan tidak ditemukan.');
        }

        $sikeuQuota = $this->sikeuService->getExtraWisudaQuota($wisudawan->nim);
        if (!$sikeuQuota['has_paid_wisuda'] && $wisudawan->status_pembayaran_sikeu !== 'lunas') {
            return redirect()->back()->with('error', 'E-Ticket belum dapat diunduh karena status pembayaran wisuda di SIKEU belum lunas.');
        }

        $periode = $wisudawan->periodeWisuda ?? PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        // Student QR Token Base64
        $studentToken = $wisudawan->qr_code_token ?: ('WSD-' . $wisudawan->nim);
        $studentQrBase64 = QrCodeService::generatePngBase64($studentToken, 6, 2);

        // Guest QR Tokens Base64
        $guestTickets = [];
        $guests = $wisudawan->tamuTambahan()->orderBy('id')->get();

        foreach ($guests as $guest) {
            $guestToken = $guest->qr_guest_token ?: ('GST-' . $guest->id . '-' . $wisudawan->nim);
            $guestTickets[] = [
                'id' => $guest->id,
                'nama_tamu' => $guest->nama_tamu,
                'hubungan' => $guest->hubungan,
                'qr_guest_token' => $guestToken,
                'qr_base64' => QrCodeService::generatePngBase64($guestToken, 6, 2),
            ];
        }

        $pdf = Pdf::loadView('pdf.tiket_wisuda', [
            'wisudawan' => $wisudawan,
            'periode' => $periode,
            'sikeuQuota' => $sikeuQuota,
            'studentQrBase64' => $studentQrBase64,
            'guestTickets' => $guestTickets,
        ])->setPaper('a4', 'portrait');

        $cleanName = str_replace(' ', '_', preg_replace('/[^A-Za-z0-9_]/', '', $wisudawan->nama_lengkap));
        return $pdf->stream("E-Ticket_Wisuda_{$wisudawan->nim}_{$cleanName}.pdf");
    }
}
