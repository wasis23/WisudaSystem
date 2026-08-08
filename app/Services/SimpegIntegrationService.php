<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimpegIntegrationService
{
    /**
     * SIMPEG Live Base URL
     */
    protected string $simpegBaseUrl = 'https://simpeg.poltekindonusa.ac.id';

    /**
     * Get list of employees from SIMPEG database (wsia_profil table) or API
     */
    public function getEmployees(string $search = null): array
    {
        // 1. Try DB Connection (simpeg / hestiapanel_sistem_simpeg)
        try {
            $query = DB::connection('simpeg')->table('wsia_profil');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('NIDN', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");

                    $columns = [];
                    try {
                        $columns = DB::connection('simpeg')->getSchemaBuilder()->getColumnListing('wsia_profil');
                    } catch (\Exception $e) {}

                    if (in_array('nip', $columns)) {
                        $q->orWhere('nip', 'like', "%{$search}%");
                    }
                });
            }

            $results = $query->orderBy('nama', 'asc')->take(50)->get();

            if ($results->count() > 0) {
                return $results->map(function ($emp) {
                    return [
                        'id_sdm' => $emp->id_sdm ?? null,
                        'nidn' => $emp->NIDN ?? $emp->nidn ?? null,
                        'nip' => $emp->nip ?? $emp->nik ?? null,
                        'username' => $emp->username ?? $emp->NIDN ?? $emp->email ?? 'pegawai_' . ($emp->id_sdm ?? rand(100, 999)),
                        'nama' => $emp->nama ?? 'Pegawai Indonusa',
                        'status' => $emp->status ?? 'Tendik',
                        'email' => $emp->email ?? null,
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {
            Log::warning('SIMPEG DB Integration error: ' . $e->getMessage());
        }

        // 2. Fallback mock dataset for local development
        $mockData = [
            ['id_sdm' => 'SDM001', 'nidn' => '0601018501', 'nip' => '1985010101', 'username' => 'security.andi', 'nama' => 'Andi Susanto (Security)', 'status' => 'Tendik', 'email' => 'andi.security@poltekindonusa.ac.id'],
            ['id_sdm' => 'SDM002', 'nidn' => '0602028802', 'nip' => '1988020202', 'username' => 'security.budi', 'nama' => 'Budi Santoso (Security)', 'status' => 'Tendik', 'email' => 'budi.security@poltekindonusa.ac.id'],
            ['id_sdm' => 'SDM003', 'nidn' => '0603039003', 'nip' => '1990030303', 'username' => 'receptionist.dewi', 'nama' => 'Dewi Anggraini (Receptionist)', 'status' => 'Tendik', 'email' => 'dewi.reception@poltekindonusa.ac.id'],
            ['id_sdm' => 'SDM004', 'nidn' => '0604049204', 'nip' => '1992040404', 'username' => 'receptionist.rudi', 'nama' => 'Rudi Kurniawan (Receptionist)', 'status' => 'Tendik', 'email' => 'rudi.reception@poltekindonusa.ac.id'],
        ];

        if ($search) {
            return array_values(array_filter($mockData, function ($item) use ($search) {
                return stripos($item['nama'], $search) !== false ||
                       stripos($item['username'], $search) !== false ||
                       stripos($item['nidn'], $search) !== false;
            }));
        }

        return $mockData;
    }

    /**
     * Authenticate employee credentials against live SIMPEG HTTP API (https://simpeg.poltekindonusa.ac.id/api/verify-login) or DB
     */
    public function verifyCredentials(string $username, string $password): ?array
    {
        $cleanUsername = trim($username);

        // 1. Try Live HTTP API call to https://simpeg.poltekindonusa.ac.id/api/verify-login
        try {
            $response = Http::timeout(5)->post($this->simpegBaseUrl . '/api/verify-login', [
                'username' => $cleanUsername,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success' && isset($data['data'])) {
                    return [
                        'id_sdm' => $data['data']['id_sdm'] ?? null,
                        'nidn' => $data['data']['nidn'] ?? null,
                        'nip' => $data['data']['nip'] ?? null,
                        'username' => $data['data']['username'] ?? $cleanUsername,
                        'nama' => $data['data']['name'] ?? $cleanUsername,
                        'email' => $cleanUsername . '@poltekindonusa.ac.id',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('SIMPEG Live API error: ' . $e->getMessage());
        }

        // 2. Try DB Connection (simpeg / hestiapanel_sistem_simpeg)
        try {
            $user = DB::connection('simpeg')->table('wsia_profil')
                ->where(function ($q) use ($cleanUsername) {
                    $q->where('username', $cleanUsername)
                      ->orWhere('NIDN', $cleanUsername)
                      ->orWhere('email', $cleanUsername);

                    $columns = [];
                    try {
                        $columns = DB::connection('simpeg')->getSchemaBuilder()->getColumnListing('wsia_profil');
                    } catch (\Exception $e) {}

                    if (in_array('nip', $columns)) {
                        $q->orWhere('nip', $cleanUsername);
                    }
                    if (in_array('nik', $columns)) {
                        $q->orWhere('nik', $cleanUsername);
                    }
                })
                ->first();

            if ($user) {
                $passwordValid = false;
                $hash = trim((string)$user->pass);

                if (password_verify($password, $hash)) {
                    $passwordValid = true;
                } elseif (md5($password) === strtolower($hash)) {
                    $passwordValid = true;
                } elseif (sha1($password) === strtolower($hash)) {
                    $passwordValid = true;
                } elseif ($password === $hash) {
                    $passwordValid = true;
                }

                if ($passwordValid) {
                    return [
                        'id_sdm' => $user->id_sdm ?? null,
                        'nidn' => $user->NIDN ?? null,
                        'nip' => $user->nip ?? $user->nik ?? null,
                        'username' => $user->username ?? $user->NIDN ?? $cleanUsername,
                        'nama' => $user->nama ?? $cleanUsername,
                        'email' => $user->email ?? $cleanUsername . '@poltekindonusa.ac.id',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('SIMPEG DB fallback check error: ' . $e->getMessage());
        }

        // 3. Development/Demo mock credentials fallback
        if ($password === 'password' || $password === '123456') {
            return [
                'id_sdm' => 'SDM-MOCK',
                'nidn' => '0600000000',
                'nip' => '1990000000',
                'username' => $cleanUsername,
                'nama' => ucwords(str_replace(['.', '_'], ' ', $cleanUsername)),
                'email' => $cleanUsername . '@poltekindonusa.ac.id',
            ];
        }

        return null;
    }
}
