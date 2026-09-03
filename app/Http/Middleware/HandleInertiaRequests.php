<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['programStudi', 'wisudawan']);
            if ($user->role === 'wisudawan' && (!$user->wisudawan || !$user->program_studi_id)) {
                $nim = strtoupper(explode('@', $user->email)[0]);
                $wisudawan = \App\Models\Wisudawan::where('nim', $nim)->first();
                if ($wisudawan) {
                    $wisudawan->update(['user_id' => $user->id]);
                    if (!$user->program_studi_id) {
                        $user->update(['program_studi_id' => $wisudawan->program_studi_id]);
                    }
                    $user->load('wisudawan', 'programStudi');
                }
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'program_studi_id' => $user->program_studi_id,
                    'program_studi' => $user->programStudi ? [
                        'id' => $user->programStudi->id,
                        'nama_prodi' => $user->programStudi->nama_prodi,
                        'kode_prodi' => $user->programStudi->kode_prodi,
                    ] : null,
                    'wisudawan' => $user->wisudawan ? [
                        'id' => $user->wisudawan->id,
                        'nim' => $user->wisudawan->nim,
                        'nama_lengkap' => $user->wisudawan->nama_lengkap,
                        'status_verifikasi' => $user->wisudawan->status_verifikasi,
                        'pas_foto' => $user->wisudawan->pas_foto,
                    ] : null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'message' => fn () => $request->session()->get('message'),
            ],
        ];
    }
}
