<?php

namespace App\Http\Requests\Auth;

use App\Models\DutyAssignment;
use App\Models\User;
use App\Models\Wisudawan;
use App\Services\SiakadAuthService;
use App\Services\SimpegIntegrationService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Strategy:
     *  1. Input diawali huruf  → NIM Mahasiswa → autentikasi via SIAKAD API
     *  2. Input semua angka    → NIDN Pegawai/Dosen → autentikasi via SIMPEG API
     *  3. Input mengandung '@' → Email Admin → autentikasi DB lokal
     *  4. Fallback             → DB lokal biasa
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginInput = trim($this->input('email'));
        $password   = $this->input('password');

        // ─────────────────────────────────────────────────────────────
        // STRATEGY A: NIM (diawali huruf) → Mahasiswa via SIAKAD & Whitelist Wisuda
        // ─────────────────────────────────────────────────────────────
        if (preg_match('/^[a-zA-Z]/', $loginInput)) {
            $nim = strtoupper($loginInput);

            // 1. Cek apakah NIM terdaftar di daftar wisudawan pada Periode Aktif
            $activePeriode = \App\Models\PeriodeWisuda::getActive() ?? \App\Models\PeriodeWisuda::latest()->first();
            
            if ($activePeriode) {
                $wisudawan = Wisudawan::where('nim', $nim)
                    ->where('periode_wisuda_id', $activePeriode->id)
                    ->first();

                if (!$wisudawan) {
                    // Cek apakah mahasiswa terdaftar di periode lain/sebelumnya
                    $wisudawanLain = Wisudawan::with('periodeWisuda')
                        ->where('nim', $nim)
                        ->latest()
                        ->first();

                    if ($wisudawanLain) {
                        $namaPeriodeLain = $wisudawanLain->periodeWisuda?->nama_periode ?? 'periode sebelumnya';
                        throw ValidationException::withMessages([
                            'email' => "Akses Ditolak: NIM {$nim} tercatat pada {$namaPeriodeLain} (Bukan periode aktif: {$activePeriode->nama_periode}). Silakan hubungi Panitia Wisuda / BAAK jika Anda seharusnya diwisuda pada periode ini.",
                        ]);
                    } else {
                        throw ValidationException::withMessages([
                            'email' => "Akses Ditolak: NIM {$nim} belum terdaftar/di-import dalam daftar wisudawan periode aktif ({$activePeriode->nama_periode}). Silakan hubungi Panitia Wisuda / BAAK untuk konfirmasi kepesertaan.",
                        ]);
                    }
                }
            } else {
                $wisudawan = Wisudawan::where('nim', $nim)->latest()->first();
                if (!$wisudawan) {
                    throw ValidationException::withMessages([
                        'email' => "Belum ada periode wisuda aktif dan NIM {$nim} belum terdaftar di sistem wisuda.",
                    ]);
                }
            }

            // 2. Verifikasi Password via SIAKAD
            $siakadAuth = app(SiakadAuthService::class);
            $mahasiswaData = $siakadAuth->verifyMahasiswa($nim, $password);

            if ($mahasiswaData) {
                $email = $mahasiswaData['email'] ?? ($nim . '@poltekindonusa.ac.id');

                // Create/update local user record for the mahasiswa
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name'             => $mahasiswaData['nama'] ?? $wisudawan->nama_lengkap,
                        'password'         => Hash::make($password),
                        'role'             => 'wisudawan',
                        'program_studi_id' => $wisudawan->program_studi_id,
                    ]
                );

                // Update Wisudawan dengan data resmi dari SIAKAD jika belum diisi
                $updateFields = [
                    'user_id' => $user->id,
                    'email'   => $email,
                ];

                if (empty($wisudawan->nama_ayah) && !empty($mahasiswaData['nama_ayah'])) {
                    $updateFields['nama_ayah'] = $mahasiswaData['nama_ayah'];
                }
                if (empty($wisudawan->nama_ibu) && !empty($mahasiswaData['nama_ibu'])) {
                    $updateFields['nama_ibu'] = $mahasiswaData['nama_ibu'];
                }
                if ((empty($wisudawan->tempat_lahir) || $wisudawan->tempat_lahir === '') && !empty($mahasiswaData['tempat_lahir'])) {
                    $updateFields['tempat_lahir'] = $mahasiswaData['tempat_lahir'];
                }
                if ((empty($wisudawan->tanggal_lahir) || $wisudawan->tanggal_lahir === '1990-01-01' || $wisudawan->tanggal_lahir === '1900-01-01') && !empty($mahasiswaData['tanggal_lahir'])) {
                    $updateFields['tanggal_lahir'] = $mahasiswaData['tanggal_lahir'];
                }
                if (empty($wisudawan->nik) && !empty($mahasiswaData['nik'])) {
                    $updateFields['nik'] = $mahasiswaData['nik'];
                }
                if (empty($wisudawan->alamat) && !empty($mahasiswaData['alamat'])) {
                    $updateFields['alamat'] = $mahasiswaData['alamat'];
                }
                if ((empty($wisudawan->nomor_hp) || $wisudawan->nomor_hp === '') && !empty($mahasiswaData['no_hp'])) {
                    $updateFields['nomor_hp'] = $mahasiswaData['no_hp'];
                }
                if (!empty($mahasiswaData['jenis_kelamin'])) {
                    $updateFields['jenis_kelamin'] = $mahasiswaData['jenis_kelamin'];
                }

                $wisudawan->update($updateFields);

                Auth::login($user, $this->boolean('remember'));
                RateLimiter::clear($this->throttleKey());
                return;
            }

            // 3. Fallback: Autentikasi lokal jika user sudah punya akun lokal (misal password default NIM atau ganti password)
            $localEmailStudents = strtolower($nim) . '@students.poltekindonusa.ac.id';
            $localEmailIndonusa = strtolower($nim) . '@poltekindonusa.ac.id';
            $localUser = User::whereIn('email', [$localEmailStudents, $localEmailIndonusa])->first();

            if ($localUser && Hash::check($password, $localUser->password)) {
                $wisudawan->update(['user_id' => $localUser->id]);
                Auth::login($localUser, $this->boolean('remember'));
                RateLimiter::clear($this->throttleKey());
                return;
            }

            // Jika password default adalah NIM
            if ($password === $nim || $password === strtolower($nim)) {
                $user = User::firstOrCreate(
                    ['email' => $localEmailStudents],
                    [
                        'name'             => $wisudawan->nama_lengkap,
                        'password'         => Hash::make($password),
                        'role'             => 'wisudawan',
                        'program_studi_id' => $wisudawan->program_studi_id,
                    ]
                );
                $wisudawan->update(['user_id' => $user->id]);
                Auth::login($user, $this->boolean('remember'));
                RateLimiter::clear($this->throttleKey());
                return;
            }

            // Kredensial salah
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => 'Password akun mahasiswa (SIAKAD) salah. Silakan coba kembali.',
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // STRATEGY B: NIDN (semua angka) → Dosen/Staf via SIMPEG / SIAKAD
        // ─────────────────────────────────────────────────────────────
        elseif (preg_match('/^[0-9]+$/', $loginInput)) {
            $nidn = $loginInput;
            $simpegService = app(SimpegIntegrationService::class);
            $simpegUser = $simpegService->verifyCredentials($nidn, $password);

            if ($simpegUser) {
                $simpegUsername = $simpegUser['username'] ?? $nidn;
                $email = $simpegUsername . '@poltekindonusa.ac.id';

                $duty = DutyAssignment::where('is_active', true)
                    ->where(function ($q) use ($nidn, $simpegUsername, $simpegUser) {
                        $q->where('simpeg_username', $nidn)
                          ->orWhere('simpeg_username', $simpegUsername)
                          ->orWhere('simpeg_nip', $simpegUser['nip'] ?? '')
                          ->orWhere('simpeg_id_sdm', $simpegUser['id_sdm'] ?? '');
                    })
                    ->first();

                $existingUser = User::where('email', $email)
                    ->orWhere('email', $simpegUser['email'] ?? '')
                    ->first();

                $role = $duty?->duty_role ?? $existingUser?->role ?? 'security';

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name'     => $simpegUser['nama'] ?? $simpegUsername,
                        'password' => Hash::make($password),
                        'role'     => $role,
                    ]
                );

                Auth::login($user, $this->boolean('remember'));
                RateLimiter::clear($this->throttleKey());
                return;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // STRATEGY C: Email / Fallback → DB Lokal (Admin Utama, dll.)
        // ─────────────────────────────────────────────────────────────
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $credentials = [
            'email'    => $isEmail ? $loginInput : $loginInput . '@poltekindonusa.ac.id',
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // ─────────────────────────────────────────────────────────────
        // STRATEGY D: Username non-angka non-huruf (pegawai manual)
        //             → SIMPEG / SIAKAD API juga
        // ─────────────────────────────────────────────────────────────
        if (!$isEmail) {
            $usernameClean = str_replace('@poltekindonusa.ac.id', '', $loginInput);
            $simpegService = app(SimpegIntegrationService::class);
            $simpegUser = $simpegService->verifyCredentials($usernameClean, $password);

            if ($simpegUser) {
                $simpegUsername = $simpegUser['username'] ?? $usernameClean;
                $email = $simpegUsername . '@poltekindonusa.ac.id';

                $duty = DutyAssignment::where('is_active', true)
                    ->where(function ($q) use ($usernameClean, $simpegUsername, $simpegUser) {
                        $q->where('simpeg_username', $usernameClean)
                          ->orWhere('simpeg_username', $simpegUsername)
                          ->orWhere('simpeg_nip', $simpegUser['nip'] ?? '')
                          ->orWhere('simpeg_id_sdm', $simpegUser['id_sdm'] ?? '');
                    })
                    ->first();

                $existingUser = User::where('email', $email)
                    ->orWhere('email', $simpegUser['email'] ?? '')
                    ->first();

                $role = $duty?->duty_role ?? $existingUser?->role ?? 'security';

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name'     => $simpegUser['nama'] ?? $usernameClean,
                        'password' => Hash::make($password),
                        'role'     => $role,
                    ]
                );

                Auth::login($user, $this->boolean('remember'));
                RateLimiter::clear($this->throttleKey());
                return;
            }
        }

        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Kredensial tidak valid. Pastikan NIM / NIDN / Email dan Password Anda benar.',
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
