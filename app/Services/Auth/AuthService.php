<?php

namespace App\Services\Auth;

use App\Mail\OtpMail;
use App\Models\FcmToken;
use App\Models\RefreshToken;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthService
{
    private const OTP_TTL_MINUTES = 10;

    /** @var array<string, bool>|null */
    private ?array $usersColumns = null;

    public function startRegistration(array $data): array
    {
        $user = $this->upsertPendingUser($data);

        $otp = $this->generateOtp();
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        $this->cacheRegistrationOtp($user->email, $otp, $expiresAt);

        $this->sendMailOtp($user->email, $otp);

        return [
            'message' => 'OTP sent to your email ' ,
            'email' => $user->email,
            'otp_expires_at' => $expiresAt,
        ];
    }

    public function verifyRegistrationOtp(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new DomainException('Invalid email or OTP.');
        }

        if ($user->email_verified_at !== null) {
            throw new DomainException('Email is already verified.');
        }

        $cachedOtp = $this->getCachedRegistrationOtpHash($email);
        if ($cachedOtp === null) {
            throw new DomainException('OTP has expired. Please register again.');
        }
    
        if (!Hash::check($otp, $cachedOtp)) {
            throw new DomainException('Invalid email or OTP.');
        }

        $this->forgetCachedRegistrationOtp($email);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $tokens = $this->issueTokens($user);

        return [
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
        ];
    }

    public function refreshAccessToken(string $plainToken): array
    {
        $hashed = hash('sha256', $plainToken);

        $checked = RefreshToken::where('token', $hashed)
            ->whereNull('revoked_at')
            ->first();

        if (!$checked) {
            throw new DomainException('Invalid refresh token.');
        }

        if ($checked->expires_at->isPast()) {
            $checked->delete();
            throw new DomainException('Refresh token expired.');
        }

        $newRefreshToken = $this->rotateRefreshToken($checked);

        $newAccessToken = $this->generateAccessToken($checked->user);

        return [
            'user' => $checked->user,
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
        ];
    }

    public function login(array $data): array
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $user = User::where('email', $email)->first();
        if (!$user || !$this->hasUsersColumn('password')) {
            throw new DomainException('Invalid credentials.');
        }

        if (!Hash::check($password, (string) $user->password)) {
            throw new DomainException('Invalid credentials.');
        }

        if ($user->email_verified_at === null) {
            throw new DomainException('Please verify your email with OTP first.');
        }

        $tokens = $this->issueTokens($user);

        return [
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
        ];
    }

    public function logout(string $plainRefreshToken,string $fcmToken, ?string $accessToken = null, bool $force = false): void
    {
        if ($fcmToken) {
            FcmToken::where('token', $fcmToken)->delete();
        }
        $hashed = hash('sha256', $plainRefreshToken);

        $refreshToken = RefreshToken::where('token', $hashed)
            ->whereNull('revoked_at')
            ->first();

        if (!$refreshToken) {
            throw new DomainException('Invalid refresh token.');
        }

        $refreshToken->forceFill([
            'revoked_at' => now(),
        ])->save();


        if ($accessToken) {
            try {
                $this->guard()->setToken($accessToken)->logout($force);
            } catch (JWTException $e) {
                // Refresh token is already revoked; logout remains successful.
            }
        }
    }
    public function UpdatePassword(User $user, array $data): void
    {
        if (!$this->hasUsersColumn('password')) {
            throw new DomainException('Password update is not supported.');
        }

        $currentPassword = (string) ($data['current_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');

        if (!Hash::check($currentPassword, (string) $user->password)) {
            throw new DomainException('Current password is incorrect.');
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();
    }

    private function upsertPendingUser(array $data): User
    {
        $email = (string) $data['email'];
        $user = User::where('email', $email)->first();

        if ($user && $user->email_verified_at !== null) {
            throw new DomainException('Email is already registered.');
        }

        if (!$user) {
            $user = new User();
            $user->email = $email;
        }

        $userName = (string) ($data['user_name'] ?? Str::before($email, '@'));
        if ($this->hasUsersColumn('user_name')) {
            $conflict = User::where('user_name', $userName)
                ->where('id', '!=', $user->id)
                ->whereNotNull('email_verified_at')
                ->exists();

            if ($conflict) {
                throw new DomainException('Username is already taken.');
            }
        }

        $this->setIfColumn($user, 'user_name', $userName);

        if ($this->hasUsersColumn('password') && !empty($data['password'])) {
            $user->password = Hash::make((string) $data['password']);
        }

        $user->save();

        return $user;
    }

    private function issueTokens(User $user): array
    {
        return [
            'access_token' => $this->generateAccessToken($user),
            'refresh_token' => $this->generateRefreshToken($user),
        ];
    }

    private function generateAccessToken(User $user): string
    {
        try {
            return $this->guard()->login($user);
        } catch (JWTException $e) {
            throw new DomainException('Unable to generate JWT access token.');
        }
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        return $guard;
    }

    private function generateRefreshToken(User $user): string
    {
        RefreshToken::where('user_id', $user->id)->delete();

        $plainToken = Str::random(96);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return $plainToken;
    }

    private function rotateRefreshToken(RefreshToken $refreshToken): string
    {
        $plainToken = Str::random(96);

        $refreshToken->forceFill([
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
            'revoked_at' => null,
        ])->save();

        return $plainToken;
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function sendMailOtp(string $email, string $otp): void
    {
        Mail::to($email)->send(new OtpMail($otp, self::OTP_TTL_MINUTES));
    }

    private function cacheRegistrationOtp(string $email, string $otp, \DateTimeInterface $expiresAt): void
    {
        Cache::put(
            $this->registrationOtpCacheKey($email),
            Hash::make($otp),
            $expiresAt
        );
    }

    private function getCachedRegistrationOtpHash(string $email): ?string
    {
        $value = Cache::get($this->registrationOtpCacheKey($email));

        return is_string($value) ? $value : null;
    }

    private function forgetCachedRegistrationOtp(string $email): void
    {
        Cache::forget($this->registrationOtpCacheKey($email));
    }

    private function registrationOtpCacheKey(string $email): string
    {
        return 'auth:register_otp:' . hash('sha256', mb_strtolower(trim($email)));
    }

    private function hasUsersColumn(string $column): bool
    {
        if ($this->usersColumns === null) {
            $this->usersColumns = array_fill_keys(Schema::getColumnListing('users'), true);
        }

        return isset($this->usersColumns[$column]);
    }

    private function setIfColumn(User $user, string $column, ?string $value): void
    {
        if ($value === null || $value === '' || !$this->hasUsersColumn($column)) {
            return;
        }

        $user->{$column} = $value;
    }
}
