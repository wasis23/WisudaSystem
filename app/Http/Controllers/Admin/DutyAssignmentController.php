<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DutyAssignment;
use App\Models\User;
use App\Services\SimpegIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class DutyAssignmentController extends Controller
{
    protected SimpegIntegrationService $simpegService;

    public function __construct(SimpegIntegrationService $simpegService)
    {
        $this->simpegService = $simpegService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $simpegEmployees = $this->simpegService->getEmployees($search);
        $dutyAssignments = DutyAssignment::orderBy('id', 'desc')->get();

        return Inertia::render('Admin/DutyAssignments', [
            'simpegEmployees' => $simpegEmployees,
            'dutyAssignments' => $dutyAssignments,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'simpeg_username' => 'required|string',
            'nama_pegawai' => 'required|string',
            'duty_role' => 'required|in:security,receptionist',
        ]);

        // Create or update DutyAssignment
        $assignment = DutyAssignment::updateOrCreate(
            ['simpeg_username' => $request->simpeg_username],
            [
                'simpeg_id_sdm' => $request->simpeg_id_sdm,
                'simpeg_nip' => $request->simpeg_nip,
                'nama_pegawai' => $request->nama_pegawai,
                'duty_role' => $request->duty_role,
                'is_active' => true,
                'assigned_by_user_id' => auth()->id(),
            ]
        );

        // Also create/update user in local database to allow seamless login
        $email = $request->simpeg_username . '@poltekindonusa.ac.id';
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $request->nama_pegawai,
                'password' => Hash::make('password'), // Will verify against SIMPEG in LoginRequest
                'role' => $request->duty_role,
            ]
        );

        return redirect()->back()->with('success', "Pegawai {$request->nama_pegawai} berhasil ditugaskan sebagai {$request->duty_role}!");
    }

    public function toggle(DutyAssignment $dutyAssignment)
    {
        $dutyAssignment->update(['is_active' => !$dutyAssignment->is_active]);

        // Sync local user role
        $email = $dutyAssignment->simpeg_username . '@poltekindonusa.ac.id';
        $user = User::where('email', $email)->first();
        if ($user) {
            if (!$dutyAssignment->is_active) {
                $user->update(['role' => 'panitia_presensi']);
            } else {
                $user->update(['role' => $dutyAssignment->duty_role]);
            }
        }

        return redirect()->back()->with('success', 'Status tugas pegawai berhasil diperbarui.');
    }

    public function destroy(DutyAssignment $dutyAssignment)
    {
        $email = $dutyAssignment->simpeg_username . '@poltekindonusa.ac.id';
        User::where('email', $email)->delete();
        $dutyAssignment->delete();

        return redirect()->back()->with('success', 'Penugasan pegawai berhasil dihapus.');
    }
}
