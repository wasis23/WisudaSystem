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
        // STRATEGY A: NIM (diawali huruf) → Mahasiswa via SIAKAD
        // ─────────────────────────────────────────────────────────────
        if (preg_match('/^[a-zA-Z]/', $loginInput)) {
            $nim = strtoupper($loginInput);
            $siakadAuth = app(SiakadAuthService::class);
            $mahasiswaData = $siakadAuth->verifyMahasiswa($nim, $password);

            if ($mahasiswaData) {
                $email = $mahasiswaData['email'] ?? ($nim . '@poltekindonusa.ac.id');

                // Create/update local user record for the mahasiswa
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name'     => $mahasiswaData['nama'],
                        'password' => Hash::make($password),
                        'role'     => 'wisudawan',
                    ]
                );

                // Auto-create or link Wisudawan profile if not exists
                if (!$user->wisudawan) {
                    Wisudawan::firstOrCreate(
                        ['nim' => $nim],
                        [
                            'user_id'    => $user->id,
                            'nama_lengkap' => $mahasiswaData['nama'],
                        ]
                    );
                } else {
                    // Ensure user_id linkage is correct
                    $user->wisudawan()->update(['user_id' => $user->id]);
                }

                Auth::login($user, $this->boolean('remember'));
                RateLimiter::clear($this->throttleKey());
                return;
            }

            // NIM format tapi SIAKAD gagal → lanjut ke fallback lokal
        }

        // ─────────────────────────────────────────────────────────────
        // STRATEGY B: NIDN (semua angka) → Dosen/Staf via SIMPEG
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

                if (!$duty) {
                    throw ValidationException::withMessages([
                        'email' => 'Akun pegawai Anda (' . ($simpegUser['nama'] ?? $nidn) . ') belum ditugaskan sebagai Security atau Receptionist oleh Admin Utama.',
                    ]);
                }

                $role = $duty->duty_role;

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
        //             → SIMPEG API juga (username simpeg seperti "andi.susanto")
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

                if (!$duty) {
                    throw ValidationException::withMessages([
                        'email' => 'Akun pegawai Anda (' . ($simpegUser['nama'] ?? $nidn) . ') belum ditugaskan sebagai Security atau Receptionist oleh Admin Utama.',
                    ]);
                }

                $role = $duty->duty_role;

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
