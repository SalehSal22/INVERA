<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\SaveFcmTokenRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\FcmToken;
use App\Services\Auth\AuthService;
use DomainException;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $authService)
    {
        try {
            $result = $authService->startRegistration($request->validated());
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 409);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to start registration',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => [
                'email' => $result['email'],
                'otp_expires_at' => $result['otp_expires_at'],
            ],
        ], 201);
    }

    public function verifyOtp(VerifyOtpRequest $request, AuthService $authService)
    {
        try {
            $result = $authService->verifyRegistrationOtp(
                $request->validated('email'),
                $request->validated('otp')
            );
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to verify OTP',
            ], 500);
        }

        return (new UserResource($result))->response()->setStatusCode(200);
    }

    public function refresh(Request $request, AuthService $authService)
    {
        $request->validate(['refresh_token' => 'required|string']);

        try {
            $result = $authService->refreshAccessToken($request->string('refresh_token')->toString());
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to refresh access token',
            ], 500);
        }

        return (new UserResource($result))->response()->setStatusCode(200);
    }

    public function login(LoginRequest $request, AuthService $authService)
    {
        try {
            $result = $authService->login($request->validated());
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to login',
            ], 500);
        }

        return (new UserResource($result))->response()->setStatusCode(200);
    }

    public function logout(LogoutRequest $request, AuthService $authService)
    {
        try {
            if ($request->has('fcm_token')) {
                $authService->logout(
                    $request->validated('refresh_token'),
                    $request->validated('fcm_token'),
                    $request->bearerToken(),
                    (bool) $request->boolean('force')
                );
            } else {
                $authService->logout(
                    $request->validated('refresh_token'),
                    "",
                    $request->bearerToken(),
                    (bool) $request->boolean('force')
                );
            }
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to logout',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }
    public function me()
    {
        return response()->json(auth('api')->user())->setStatusCode(200);
    }


    public function saveFcmToken(SaveFcmTokenRequest $request)
    {
        $validatedData = $request->validated();

        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated.',
            ], 401);
        }

        try {
            $fcmToken = FcmToken::updateOrCreate(
                ['token' => $validatedData['token']],
                ['user_id' => $user->id]
            );
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to save FCM token.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'FCM token saved successfully.',
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request, AuthService $authService)
    {

        $request->validated();
        try {
            $authService->updatePassword($request->user(), $request->validated());
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update password',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.',
        ]);
    }
}
