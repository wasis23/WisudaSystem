<?php

namespace App\Http\Requests\Auth;

use App\Models\DutyAssignment;
use App\Models\User;
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
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginInput = trim($this->input('email'));
        $password = $this->input('password');

        // 1. First try standard local Laravel authentication
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $credentials = [
            $isEmail ? 'email' : 'email' => $isEmail ? $loginInput : $loginInput . '@poltekindonusa.ac.id',
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // 2. Try SIMPEG integration API lookup for all SIMPEG Officers / Duty Officers
        $usernameClean = str_replace('@poltekindonusa.ac.id', '', $loginInput);
        $simpegService = app(SimpegIntegrationService::class);
        $simpegUser = $simpegService->verifyCredentials($usernameClean, $password);

        if ($simpegUser) {
            $simpegUsername = $simpegUser['username'] ?? $usernameClean;
            $duty = DutyAssignment::where('is_active', true)
                ->where(function ($q) use ($usernameClean, $simpegUsername, $simpegUser) {
                    $q->where('simpeg_username', $usernameClean)
                      ->orWhere('simpeg_username', $simpegUsername)
                      ->orWhere('simpeg_nip', $simpegUser['nip'] ?? '')
                      ->orWhere('simpeg_id_sdm', $simpegUser['id_sdm'] ?? '');
                })
                ->first();

            $role = $duty ? $duty->duty_role : 'panitia_presensi';
            $email = $simpegUsername . '@poltekindonusa.ac.id';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $simpegUser['nama'] ?? $usernameClean,
                    'password' => Hash::make($password),
                    'role' => $role,
                ]
            );

            Auth::login($user, $this->boolean('remember'));
            RateLimiter::clear($this->throttleKey());
            return;
        }

        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
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

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
