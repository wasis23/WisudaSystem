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
