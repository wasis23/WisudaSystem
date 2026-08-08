<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimpegIntegrationService
{
    /**
     * Get API URL and API Key from config/env
     */
    protected function getApiConfig(): array
    {
        $url = config('services.simpeg.url') ?: env('SIMPEG_API_URL', 'https://simpeg.poltekindonusa.ac.id/api/verify-login');
        $key = config('services.simpeg.key') ?: env('SIMPEG_API_KEY', 'e844f45c5100479b91c0eb97793a84b8b85cc2fe21f50caf38807ff72408e143');

        $baseUrl = str_replace('/api/verify-login', '', $url);

        return [
            'verify_url' => $url,
            'employees_url' => rtrim($baseUrl, '/') . '/api/employees',
            'key' => $key,
        ];
    }

    /**
     * Get list of employees exclusively from SIMPEG API or SIMPEG database (wsia_profil table)
     */
    public function getEmployees(?string $search = null): array
    {
        $apiConfig = $this->getApiConfig();
        $employees = [];

        // 1. Fetch complete employees list from Live SIMPEG HTTP API
        // Note: We don't pass search parameter in GET URL because live SIMPEG API throws 500 error when search query string is appended.
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiConfig['key'],
                'Accept' => 'application/json',
            ])->timeout(8)->get($apiConfig['employees_url']);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success' && isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                    $employees = $data['data'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('SIMPEG Employees API error: ' . $e->getMessage());
        }

        // 2. Try Direct DB Connection (simpeg / hestiapanel_sistem_simpeg) if API returned empty
        if (empty($employees)) {
            try {
                $query = DB::connection('simpeg')->table('wsia_profil');
                $results = $query->orderBy('nama', 'asc')->get();

                if ($results->count() > 0) {
                    $employees = $results->map(function ($emp) {
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
        }

        // Filter results locally in-memory if search parameter is provided
        if ($search && !empty($employees)) {
            $cleanSearch = strtolower(trim($search));
            $employees = array_values(array_filter($employees, function ($emp) use ($cleanSearch) {
                $nama = strtolower($emp['nama'] ?? '');
                $username = strtolower($emp['username'] ?? '');
                $nidn = strtolower($emp['nidn'] ?? '');
                $nip = strtolower($emp['nip'] ?? '');

                return str_contains($nama, $cleanSearch) ||
                       str_contains($username, $cleanSearch) ||
                       str_contains($nidn, $cleanSearch) ||
                       str_contains($nip, $cleanSearch);
            }));
        }

        return $employees;
    }

    /**
     * Authenticate employee credentials exclusively against live SIMPEG HTTP API or SIMPEG DB
     */
    public function verifyCredentials(string $username, string $password): ?array
    {
        $cleanUsername = trim($username);
        $apiConfig = $this->getApiConfig();

        // 1. Try Live HTTP API call to https://simpeg.poltekindonusa.ac.id/api/verify-login with X-API-KEY
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiConfig['key'],
                'Accept' => 'application/json',
            ])->timeout(5)->post($apiConfig['verify_url'], [
                'username' => $cleanUsername,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success' && isset($data['data'])) {
                    return [
                        'id_sdm' => $data['data']['id_sdm'] ?? null,
                        'nidn' => $data['data']['nidn'] ?? $data['data']['username'] ?? null,
                        'nip' => $data['data']['nip'] ?? null,
                        'username' => $data['data']['username'] ?? $cleanUsername,
                        'nama' => $data['data']['name'] ?? $cleanUsername,
                        'email' => $cleanUsername . '@poltekindonusa.ac.id',
                    ];
                }
            } else {
                Log::warning('SIMPEG Verify Login API response failed (' . $response->status() . '): ' . $response->body());
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

        return null;
    }
}
