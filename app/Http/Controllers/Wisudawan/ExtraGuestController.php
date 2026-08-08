<?php

namespace App\Http\Controllers\Wisudawan;

use App\Http\Controllers\Controller;
use App\Models\WisudawanTamuTambahan;
use App\Services\SikeuIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ExtraGuestController extends Controller
{
    protected SikeuIntegrationService $sikeuService;

    public function __construct(SikeuIntegrationService $sikeuService)
    {
        $this->sikeuService = $sikeuService;
    }

    public function index()
    {
        $user = auth()->user();
        $wisudawan = $user->wisudawan ? $user->wisudawan->load(['programStudi', 'tamuTambahan']) : null;

        if (!$wisudawan) {
            return redirect()->route('wisudawan.dashboard')->with('error', 'Silakan lengkapi biodata wisudawan terlebih dahulu.');
        }

        // Fetch SIKEU financial quota
        $quotaData = $this->sikeuService->getExtraWisudaQuota($wisudawan->nim);
        $totalAllowedGuests = $quotaData['total_allowed_guests'];
        $wisudawan->update(['jumlah_tamu_tambahan' => $totalAllowedGuests]);

        return Inertia::render('Wisudawan/ExtraGuestForm', [
            'wisudawan' => $wisudawan,
            'quotaData' => $quotaData,
            'tamuTambahan' => $wisudawan->tamuTambahan,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $wisudawan = $user->wisudawan;

        if (!$wisudawan) {
            return redirect()->back()->with('error', 'Wisudawan tidak ditemukan.');
        }

        $request->validate([
            'guests' => 'required|array',
            'guests.*.nama_tamu' => 'required|string',
            'guests.*.hubungan' => 'required|string',
        ]);

        $quotaData = $this->sikeuService->getExtraWisudaQuota($wisudawan->nim);
        $maxGuests = $quotaData['total_allowed_guests'];

        if (count($request->guests) > $maxGuests) {
            return redirect()->back()->with('error', "Jumlah pendamping/tamu melebihi batas kuota ({$maxGuests} orang).");
        }

        // Sync extra guests
        WisudawanTamuTambahan::where('wisudawan_id', $wisudawan->id)->delete();

        foreach ($request->guests as $guest) {
            WisudawanTamuTambahan::create([
                'wisudawan_id' => $wisudawan->id,
                'nama_tamu' => $guest['nama_tamu'],
                'hubungan' => $guest['hubungan'],
                'qr_guest_token' => 'GST-' . strtoupper(Str::random(8)),
            ]);
        }

        return redirect()->back()->with('success', 'Data tamu undangan tambahan berhasil disimpan! Rincian snack telah dikalkulasi.');
    }
}
