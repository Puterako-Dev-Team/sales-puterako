<?php
// filepath: c:\laragon\www\sales-puterako\app\Http\Controllers\AuthController.php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        // Redirect ke dashboard jika sudah login
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email', 'remember'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            $userRole = strtolower($user->role ?? '');
            $userDepartemen = strtolower($user->departemen ?? '');
            if ($userRole === 'staff' && $userDepartemen === 'sales') {
                $now = now();
                $today = $now->toDateString();

                // Cek holiday hari ini
                $holiday = Holiday::where('tanggal_libur', $today)->first();

                // 1. Jika hari ini libur nasional, tolak login
                if ($holiday && $holiday->libur_nasional) {
                    Auth::logout();
                    toast('Hari ini adalah Libur Nasional. Sales tidak dapat login.', 'error');
                    return redirect()->back()->withInput($request->only('email', 'remember'));
                }

                // Cek jam kerja
                $workStartHour = 8;
                $workEndHour = 17;
                $currentDayOfWeek = $now->dayOfWeek;
                $currentHour = $now->hour;

                $isWeekend = in_array($currentDayOfWeek, [0, 6]);
                $isOutsideWorkingHours = $currentHour < $workStartHour || $currentHour >= $workEndHour;

                if ($isWeekend || $isOutsideWorkingHours) {
                    Auth::logout();
                    toast('Akses untuk Sales hanya dapat dilakukan pada jam kerja (Senin - Jumat, 08:00 - 17:00) Hubungi Administrator Jika Perlu Bantuan.', 'error');
                    return redirect()->back()->withInput($request->only('email', 'remember'));
                }
            }

            $request->session()->regenerate();
            toast('Login berhasil! Selamat datang, ' . $user->name, 'success');
            return redirect()->route('dashboard');
        }

        // login gagal (email/password salah)
        toast('Email atau password salah.', 'error');
        return redirect()->back()
            ->withInput($request->only('email', 'remember'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logout berhasil.');
    }
}