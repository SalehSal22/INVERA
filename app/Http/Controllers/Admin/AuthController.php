<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Services\Auth\AdminAuthService;
use DomainException;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    public function login(AdminLoginRequest $request, AdminAuthService $authService)
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
                'message' => 'Unable to login.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'admin' => [
                    'id' => $result['admin']->id,
                    'name' => $result['admin']->name,
                    'email' => $result['admin']->email,
                ],
                'access_token' => $result['access_token'],
            ],
        ]);
    }

    public function logout(AdminAuthService $authService)
    {
        try {
            $authService->logout();
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to logout.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }
    public function me( )
    {
        try {
            $admin = auth('admin')->user();
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to retrieve admin details.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ],
            ],
        ]);
    }
    public function changeName(AdminAuthService $authService,Request $request)
    {
        try {
            $admin = auth('admin')->user();
            $newName = $request->validate(['name' => 'required|string|max:255']);
            $updatedAdmin = $authService->changeName($admin, $newName['name']);
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to change admin name.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'admin' => [
                    'id' => $updatedAdmin->id,
                    'name' => $updatedAdmin->name,
                    'email' => $updatedAdmin->email,
                ],
            ],
        ]);
    }
    public function changePassword(AdminAuthService $authService, Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $response = $authService->changePassword(auth('admin')->user(), $request->only('current_password', 'new_password'));

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully.',
        ]);
    }
    // public function changeEmail(AdminAuthService $authService, Request $request){
        
    //     $request->validate([
    //         'new_email' => 'required|email|unique:admins,email',
    //         'password' => 'required|string',
    //     ]);

    //     $response = $authService->changeEmail(auth('admin')->user(), $request->only('new_email', 'old_email', 'password'));

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Otp sent to new email for verification.',
    //         'data' => $response
    //     ]);
    // }
    // public function verifyChangeEmailOtp(AdminAuthService $authService, Request $request){
    //     $request->validate([
    //         'new_email' => 'required|email',
    //         'otp' => 'required|string',
    //     ]);

    //     $response = $authService->verifyChangeEmailOtp(auth('admin')->user(), $request->only('new_email', 'otp'));

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Email changed successfully.',
    //         'data' => [
    //             'admin' => [
    //                 'name' => $response['admin']->name,
    //                 'email' => $response['admin']->email,
    //             ],
    //         ],
    //     ]);
    // }
    
}
