<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Requests\Auth\InternLoginRequest;
use App\Http\Requests\Auth\InternRegisterRequest;
use App\Models\AdminUser;
use App\Models\Intern;
use App\Models\InternAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function adminLogin(AdminLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $admin = AdminUser::where('email', $data['email'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return $this->error('Email atau password salah.', 401);
        }

        if (! $admin->is_active) {
            return $this->error('Akun admin tidak aktif.', 403);
        }

        $token = $admin->createToken('admin-auth-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'title' => $admin->title,
            ],
        ]);
    }

    public function internRegister(InternRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $intern = DB::transaction(function () use ($data) {
            $account = InternAccount::create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_verified' => false,
            ]);

            return Intern::create([
                'intern_account_id' => $account->id,
                'full_name' => $data['full_name'],
                'nim' => $data['nim'],
                'institution' => $data['institution'],
                'major' => $data['major'],
                'phone' => $data['phone'] ?? null,
                'division_id' => null,
                'mentor_id' => null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'pending',
                'registered_via' => 'self',
            ]);
        });

        $account = $intern->account;
        $token = $account->createToken('intern-auth-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'intern' => $intern,
        ], null, 201);
    }

    public function internLogin(InternLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $account = InternAccount::where('email', $data['email'])->first();

        if (! $account || ! Hash::check($data['password'], $account->password)) {
            return $this->error('Email atau password salah.', 401);
        }

        $token = $account->createToken('intern-auth-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'intern' => $account->intern,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Berhasil logout.');
    }
}
