<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiakadAuthService
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = env('SIAKAD_API_URL', 'https://siakad.poltekindonusa.ac.id/api/mahasiswa_external.php');
        $this->apiKey = env('SIAKAD_API_KEY', 'INDONUSA_SECRET_API_KEY_2026_X7Z');
    }

    /**
     * Authenticate a mahasiswa via NIM + password against SIAKAD API.
     * SIAKAD returns password_hash which we verify locally.
     *
     * @return array|null  Returns mahasiswa data array if credentials valid, null otherwise.
     */
    public function verifyMahasiswa(string $nim, string $password): ?array
    {
        $cleanNim = strtoupper(trim($nim));

        // 1. Try SIAKAD HTTP API
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept'    => 'application/json',
            ])->timeout(8)->get($this->apiUrl, ['nim' => $cleanNim]);

            if ($response->successful()) {
                $data = $response->json();

                // API returns single data object when NIM is specified
                $mahasiswa = $data['data'] ?? null;

                if ($mahasiswa && isset($mahasiswa['password_hash'])) {
                    if (password_verify($password, $mahasiswa['password_hash'])) {
                        return [
                            'nim'          => $mahasiswa['nim'] ?? $cleanNim,
                            'nama'         => $mahasiswa['nama'] ?? $cleanNim,
                            'email'        => $mahasiswa['email_institusi'] ?? $mahasiswa['email'] ?? ($cleanNim . '@poltekindonusa.ac.id'),
                            'prodi'        => $mahasiswa['prodi'] ?? null,
                            'no_hp'        => $mahasiswa['no_hp'] ?? null,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('SIAKAD Auth API error: ' . $e->getMessage());
        }

        // 2. Try Direct DB connection to SIAKAD (fallback)
        try {
            $student = \Illuminate\Support\Facades\DB::connection('siakad')
                ->table('viewMahasiswaPt')
                ->where('nipd', $cleanNim)
                ->first();

            if (!$student) {
                $student = \Illuminate\Support\Facades\DB::connection('siakad')
                    ->table('viewMahasiswaKeluar')
                    ->where('nipd', $cleanNim)
                    ->first();
            }

            if ($student) {
                $hash = trim((string)($student->pass ?? $student->password ?? ''));
                $passwordValid = false;

                if ($hash && password_verify($password, $hash)) {
                    $passwordValid = true;
                } elseif ($hash && md5($password) === strtolower($hash)) {
                    $passwordValid = true;
                } elseif ($hash && sha1($password) === strtolower($hash)) {
                    $passwordValid = true;
                } elseif ($hash && $password === $hash) {
                    $passwordValid = true;
                }

                if ($passwordValid) {
                    return [
                        'nim'   => $student->nipd ?? $cleanNim,
                        'nama'  => $student->nm_pd ?? $student->nama ?? $cleanNim,
                        'email' => strtolower($cleanNim) . '@students.poltekindonusa.ac.id',
                        'prodi' => $student->nm_lemb ?? $student->nm_prodi ?? null,
                        'no_hp' => $student->telepon_seluler ?? $student->telepon ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('SIAKAD DB Auth error: ' . $e->getMessage());
        }

        return null;
    }
}
