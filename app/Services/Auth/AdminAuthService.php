<?php

namespace App\Services\Auth;

use App\Mail\OtpEmailChangeMail;
use App\Models\Admin;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AdminAuthService
{

    private const OTP_TTL_MINUTES = 10;
    public function login(array $data): array
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $admin = Admin::where('email', $email)->first();
        if (!$admin || !Hash::check($password, (string) $admin->password)) {
            throw new DomainException('Invalid credentials.');
        }

        try {
            $token = $this->guard()->login($admin);
        } catch (JWTException $e) {
            throw new DomainException('Unable to generate admin token.');
        }

        return [
            'admin' => $admin,
            'access_token' => $token,
        ];
    }

    public function logout(): void
    {
        try {
            $this->guard()->logout();
        } catch (JWTException $e) {
            // Token might already be invalidated.
        }
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('admin');

        return $guard;
    }
    public function changeName(Admin $admin, string $newName): Admin
    {
        $admin->name = $newName;
        $admin->save();

        return $admin;
    }
    public function changePassword(Admin $admin, array $data): Admin
    {
        if (!Hash::check($data['current_password'], $admin->password)) {
            throw new DomainException('Current password is incorrect.');
        }
        $admin->password = Hash::make($data['new_password']);
        $admin->save();

        return $admin;
    }
    // public function changeEmail(Admin $admin, array $data)
    // {
    //     if (!Hash::check($data['password'], $admin->password)) {
    //         throw new DomainException('Current password is incorrect.');
    //     }
    //     $otp = $this->generateOtp();
    //     $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

    //     $this->cacheEmailChangedOtp($data['new_email'], $otp, $expiresAt);

    //     $this->sendMailOtp($data['new_email'], $otp);

    //     return [
    //         'message' => 'OTP sent to your email ',
    //         'email' => $data['new_email'],
    //         'otp_expires_at' => $expiresAt,
    //     ];
    // }
    // public function verifyChangeEmailOtp(Admin $admin, array $data)
    // {
    //     $cachedOtp = $this->getCachedEmailChangedOtpHash($data['new_email']);
    //     if ($cachedOtp === null) {
    //         throw new DomainException('OTP has expired. Please request a new one.');
    //     }
    
    //     if (!Hash::check($data['otp'], $cachedOtp)) {
    //         throw new DomainException('Invalid OTP.');
    //     }

    //     $this->forgetCachedEmailChangedOtp($data['new_email']);

    //     $admin->email = $data['new_email'];
    //     $admin->save();

    //     return [
    //         'message' => 'Email changed successfully.',
    //         'admin' => $admin,
    //     ];
    // }
    //     private function getCachedEmailChangedOtpHash(string $email): ?string
    // {
    //     $value = Cache::get($this->ChangeEmailOtpCacheKey($email));

    //     return is_string($value) ? $value : null;
    // }
    //     private function forgetCachedEmailChangedOtp(string $email): void
    // {
    //     Cache::forget($this->ChangeEmailOtpCacheKey($email));
    // }
    // private function generateOtp(): string
    // {
    //     return (string) random_int(100000, 999999);
    // }
    // private function cacheEmailChangedOtp(string $email, string $otp, \DateTimeInterface $expiresAt): void
    // {
    //     Cache::put(
    //         $this->ChangeEmailOtpCacheKey($email),
    //         Hash::make($otp),
    //         $expiresAt
    //     );
    // }
    // public function sendMailOtp(string $email, string $otp): void
    // {
    //     Mail::to($email)->send(new OtpEmailChangeMail($otp, self::OTP_TTL_MINUTES));
    // }
    // private function ChangeEmailOtpCacheKey(string $email): string
    // {
    //     return 'auth:change_email_otp:' . hash('sha256', mb_strtolower(trim($email)));
    // }
}
